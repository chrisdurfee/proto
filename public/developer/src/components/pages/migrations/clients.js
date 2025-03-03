export const clients = Array.from({ length: 20 }, (_, index) => ({
    id: index + 1,
    name: `Client ${index + 1}`,
    status: index % 2 === 0 ? 'Active' : 'Inactive',
    email: `client${index + 1}@example.com`,
    totalAmount: (Math.random() * 1000).toFixed(2),
    image: `https://placekitten.com/100/100?image=${index + 1}`,
    role: index % 2 === 0 ? 'Premium Member' : 'Basic Member',
}));