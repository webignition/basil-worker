<?php

declare(strict_types=1);

namespace App\Model\Workflow;

use App\Entity\Job;

class ApplicationWorkflow extends AbstractWorkflow
{
    public function __construct(?Job $job, bool $callbackWorkflowIsComplete)
    {
        parent::__construct($this->deriveState($job, $callbackWorkflowIsComplete));
    }

    /**
     * @return WorkflowInterface::STATE_*
     */
    private function deriveState(?Job $job, bool $callbackWorkflowIsComplete): string
    {
        if (null === $job) {
            return WorkflowInterface::STATE_NOT_READY;
        }

        $jobState = $job->getState();

        if (Job::STATE_COMPILATION_AWAITING === $jobState) {
            return WorkflowInterface::STATE_NOT_STARTED;
        }

        if ($job->isRunning()) {
            return WorkflowInterface::STATE_IN_PROGRESS;
        }

        if (Job::STATE_EXECUTION_CANCELLED === $jobState) {
            return WorkflowInterface::STATE_COMPLETE;
        }

        return $callbackWorkflowIsComplete
            ? WorkflowInterface::STATE_COMPLETE
            : WorkflowInterface::STATE_IN_PROGRESS;
    }
}
