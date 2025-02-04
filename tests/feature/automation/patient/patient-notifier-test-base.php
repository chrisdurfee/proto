<?php declare(strict_types=1);
namespace Tests\Feature\Automation\Patient;

use Tests\Feature\PatientTestTrait;
use Tests\Feature\Automation\NotifierTestBase;

/**
 * PatientNotifierTestBase
 *
 * This is the base class for patient notifier tests.
 *
 * @package Tests\Feature\Automation
 * @abstract
 */
abstract class PatientNotifierTestBase extends NotifierTestBase
{
    use PatientTestTrait;

    /**
     * This will be called before each test.
     *
     * @return void
     */
    public function setup(): void
    {
        parent::setup();

        $this->getTestPatient();
    }
}