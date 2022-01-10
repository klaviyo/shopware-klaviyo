<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class BackgroundJobScheduler implements SchedulerInterface
{
    private EntityRepositoryInterface $jobRepository;
    private SerializerInterface $messageSerializer;
    private MessageBusInterface $messageBus;

    public function __construct(
        EntityRepositoryInterface $jobRepository,
        SerializerInterface $messageSerializer,
        MessageBusInterface $messageBus
    ) {
        $this->jobRepository = $jobRepository;
        $this->messageSerializer = $messageSerializer;
        $this->messageBus = $messageBus;
    }

    public function scheduleJob(Context $context, JobEntity $job, Envelope $message): JobEntity
    {
        $serializedEnvelope = $this->messageSerializer->encode($message);
        $job->setMessage($serializedEnvelope['body'] ?? null);

        $this->jobRepository->create([$job->toArray()],$context);
        $this->messageBus->dispatch($message);

        return $job;
    }
}
