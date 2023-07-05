<?php declare(strict_types=1);

namespace Od\Scheduler\Decorator;

use Od\Scheduler\Async\{JobMessageInterface, ParentAwareMessageInterface};
use Od\Scheduler\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteTypeIntendException;
use Symfony\Component\Messenger\{Envelope, MessageBusInterface};
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class MessageBusDecorator implements MessageBusInterface
{
    private MessageBusInterface $innerBus;
    private SerializerInterface $messageSerializer;
    private EntityRepositoryInterface $jobRepository;

    public function __construct(
        MessageBusInterface $innerBus,
        SerializerInterface $messageSerializer
    ) {
        $this->innerBus = $innerBus;
        $this->messageSerializer = $messageSerializer;
    }

    public function dispatch($message, array $stamps = []): Envelope
    {
        $jobMessage = $message instanceof Envelope ? $message->getMessage() : $message;
        if ($jobMessage instanceof JobMessageInterface) {
            try {
                $this->scheduleMessage($jobMessage);
            } catch (WriteTypeIntendException $e) {
                null;
            }
        }

        return $this->innerBus->dispatch($message, $stamps);
    }

    private function scheduleMessage($jobMessage)
    {
        $serializedEnvelope = $this->messageSerializer->encode(Envelope::wrap($jobMessage));
        $jobData = [
            'id' => $jobMessage->getJobId(),
            'name' => $jobMessage->getJobName(),
            'status' => JobEntity::TYPE_PENDING,
            'type' => $jobMessage->getHandlerCode(),
            'message' => $serializedEnvelope['body'] ?? null
        ];

        if ($jobMessage instanceof ParentAwareMessageInterface) {
            $jobData['parentId'] = $jobMessage->getParentJobId();
        }

        $this->jobRepository->create([$jobData], Context::createDefaultContext());
    }

    public function setJobRepository(EntityRepositoryInterface $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }
}