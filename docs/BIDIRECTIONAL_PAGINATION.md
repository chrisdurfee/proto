# Bi-Directional Cursor Pagination

The Proto framework now supports bi-directional cursor-based pagination, allowing you to efficiently fetch both historical records (scrolling up) and newer records (real-time updates) without breaking existing functionality.

## Overview

The pagination system uses two separate cursor mechanisms:

1. **`cursor`** - For fetching older/previous records (backward pagination)
2. **`since`** - For fetching newer records after a specific point (forward pagination)

This is ideal for chat applications, activity feeds, and any interface that needs to:
- Load historical data when scrolling up
- Fetch new items as they arrive
- Maintain efficient keyset pagination in both directions

## How It Works

### Backward Pagination (Historical Data)

Use the `cursor` modifier to fetch older records:

```php
// Initial load - get the most recent 20 messages
$result = $messageStorage->all(
    filter: ['conversation_id' => 123],
    limit: 20,
    modifiers: [
        'orderBy' => ['id' => 'DESC']  // Newest first
    ]
);

$messages = $result->rows;
$lastCursor = $result->lastCursor;  // ID of the oldest message fetched

// When user scrolls up, fetch older messages
$olderResult = $messageStorage->all(
    filter: ['conversation_id' => 123],
    limit: 20,
    modifiers: [
        'cursor' => $lastCursor,  // Fetch messages older than this
        'orderBy' => ['id' => 'DESC']
    ]
);
```

### Forward Pagination (Newer Data)

Use the `since` modifier to fetch newer records:

```php
// Get the ID of the newest message currently displayed
$newestMessageId = $messages[0]->id;

// Poll for new messages
$newResult = $messageStorage->all(
    filter: ['conversation_id' => 123],
    limit: 50,  // Check for up to 50 new messages
    modifiers: [
        'since' => $newestMessageId,  // Fetch messages newer than this
        'orderBy' => ['id' => 'DESC']
    ]
);

if (!empty($newResult->rows)) {
    // New messages arrived, prepend them to your display
    $newMessages = $newResult->rows;
}
```

## Complete Conversation Example

Here's a complete example for a chat/conversation interface:

### Controller Method

```php
namespace Modules\Chat\Controllers;

use Proto\Controllers\ApiController;
use Proto\Http\Router\Request;
use Modules\Chat\Models\Message;

class ConversationController extends ApiController
{
    /**
     * Get messages for a conversation
     * Supports both backward (cursor) and forward (since) pagination
     */
    public function get(Request $req): object
    {
        $conversationId = $req->getInt('conversation_id');
        $cursor = $req->input('cursor');  // For older messages
        $since = $req->input('since');    // For newer messages
        $limit = $req->getInt('limit', 20);

        if (!$conversationId) {
            return $this->error('Conversation ID required', 400);
        }

        $model = new Message();
        $storage = $model->storage();

        $modifiers = [
            'orderBy' => ['id' => 'DESC']  // Always newest first
        ];

        // Add cursor or since based on request
        if ($cursor) {
            $modifiers['cursor'] = $cursor;
        }

        if ($since) {
            $modifiers['since'] = $since;
        }

        $result = $storage->all(
            filter: ['conversation_id' => $conversationId],
            limit: $limit,
            modifiers: $modifiers
        );

        return $this->success([
            'messages' => $result->rows,
            'lastCursor' => $result->lastCursor ?? null,
            'hasMore' => count($result->rows) === $limit
        ]);
    }
}
```

### Frontend Usage (JavaScript)

```javascript
class ConversationView {
    constructor(conversationId) {
        this.conversationId = conversationId;
        this.messages = [];
        this.oldestCursor = null;
        this.newestId = null;
    }

    // Initial load
    async loadInitial() {
        const response = await fetch(`/chat/conversation?conversation_id=${this.conversationId}&limit=20`);
        const data = await response.json();

        this.messages = data.messages;
        this.oldestCursor = data.lastCursor;
        this.newestId = data.messages[0]?.id;

        this.render();
    }

    // Load older messages when scrolling up
    async loadOlder() {
        if (!this.oldestCursor) return;

        const response = await fetch(
            `/chat/conversation?conversation_id=${this.conversationId}&cursor=${this.oldestCursor}&limit=20`
        );
        const data = await response.json();

        // Append older messages to the bottom of our array
        this.messages.push(...data.messages);
        this.oldestCursor = data.lastCursor;

        this.render();
    }

    // Poll for new messages
    async checkForNew() {
        if (!this.newestId) return;

        const response = await fetch(
            `/chat/conversation?conversation_id=${this.conversationId}&since=${this.newestId}&limit=50`
        );
        const data = await response.json();

        if (data.messages.length > 0) {
            // Prepend new messages to the top of our array
            this.messages.unshift(...data.messages);
            this.newestId = data.messages[0].id;

            this.render();
        }
    }

    // Start polling for new messages every 3 seconds
    startPolling() {
        setInterval(() => this.checkForNew(), 3000);
    }
}
```

## API Request Examples

### Initial Load
```
GET /chat/conversation?conversation_id=123&limit=20
```

### Load Older Messages (Scroll Up)
```
GET /chat/conversation?conversation_id=123&cursor=456&limit=20
```

### Check for New Messages
```
GET /chat/conversation?conversation_id=123&since=999&limit=50
```

## Technical Details

### How Cursors Work

- **Cursor (Backward)**: Uses `WHERE id < ?` (for DESC order) or `WHERE id > ?` (for ASC order)
- **Since (Forward)**: Always uses `WHERE id > ?` regardless of sort order
- Both use keyset pagination for efficient database queries
- No expensive `OFFSET` calculations needed

### Order By Direction

The system respects your `orderBy` modifier:

- **DESC** (default for chat): Newest records first
  - `cursor`: Fetches records with `id < cursor_value`
  - `since`: Fetches records with `id > since_value`

- **ASC**: Oldest records first
  - `cursor`: Fetches records with `id > cursor_value`
  - `since`: Fetches records with `id > since_value`

### Performance Benefits

1. **Keyset Pagination**: Uses indexed ID column for fast queries
2. **Stateless**: No server-side pagination state to maintain
3. **Consistent**: Results remain consistent even as new data arrives
4. **Efficient**: Avoids `COUNT(*)` queries and expensive offsets

## Best Practices

1. **Always use ORDER BY**: Ensure consistent ordering with `orderBy` modifier
2. **Limit polling frequency**: Don't poll for new messages too frequently
3. **Handle empty results**: Check if new results are empty before updating UI
4. **Store both cursors**: Track both oldest cursor (for history) and newest ID (for updates)
5. **Set reasonable limits**: Use smaller limits for polling (e.g., 50), larger for initial load (e.g., 20-50)

## Migration from Existing Code

Your existing code using `cursor` continues to work without changes:

```php
// This still works exactly as before
$result = $storage->all(
    filter: $filter,
    limit: 20,
    modifiers: ['cursor' => $lastId]
);
```

Simply add `since` support when you need forward pagination:

```php
// New functionality
$result = $storage->all(
    filter: $filter,
    limit: 20,
    modifiers: ['since' => $newestId]
);
```

## Troubleshooting

**Q: New messages aren't showing up**
- Ensure you're using the newest message ID for the `since` parameter
- Check that your `orderBy` is set correctly
- Verify the filter includes all necessary criteria

**Q: Duplicate messages appearing**
- Make sure you're not mixing `cursor` and `since` in the same request
- Check that you're updating the `newestId` after each successful poll

**Q: Performance issues**
- Ensure your ID column is indexed
- Use appropriate `limit` values (20-50 is typical)
- Consider using WebSockets for real-time updates instead of polling

## See Also

- [Proto Testing Best Practices](TESTING_BEST_PRACTICES.md)
- [Factory Quick Reference](FACTORY_QUICK_REFERENCE.md)
