<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\DataFixtures\Subscriber;

use Klaviyo\Integration\Tests\DataFixtures;
use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GenerateNewsletterRecipients implements DataFixtures\TestDataFixturesInterface,
    DataFixtures\DependentTestDataFixtureInterface
{
    private const INSERT_BATCH_SIZE = 500;
    private int $recordsNumber;

    public function __construct(int $recordsNumber = 100)
    {
        $this->recordsNumber = $recordsNumber;
    }

    public function getDependenciesList(): array
    {
        return [
            new DataFixtures\RegisterDefaultSalesChannel()
        ];
    }

    public function execute(ContainerInterface $container, DataFixtures\ReferencesRegistry $referencesRegistry)
    {
        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $referencesRegistry->getByReference('klaviyo_tracking_integration.sales_channel.storefront');
        /** @var EntityRepositoryInterface $recipientRepository */
        $recipientRepository = $container->get('newsletter_recipient.repository');
        $context = Context::createDefaultContext();

        $createdAt = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $recipients = [];

        $faker = new \Faker\Generator();
        $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
        $faker->addProvider(new \Faker\Provider\en_US\Address($faker));

        for ($i = 0; $i < $this->recordsNumber; $i++) {
            $recipients[] = [
                'id' => Uuid::randomHex(),
                'email' => 'phpunit_' . Uuid::randomHex() . '@gmail.com',
                'title' => null,
                'firstName' => $faker->firstName,
                'lastName' => $faker->lastName,
                'zipCode' => $faker->postcode,
                'city' => $faker->city,
                'street' => $faker->streetName,
                'salutationId' => null,
                'customFields' => null,
                'languageId' => $context->getLanguageId(),
                'salesChannelId' => $salesChannelEntity->getId(),
                'status' => NewsletterSubscribeRoute::STATUS_OPT_IN,
                'hash' => Uuid::randomHex(),
                'createdAt' => $createdAt,
            ];
        }

        foreach (array_chunk($recipients, self::INSERT_BATCH_SIZE) as $recipientBatch) {
            $recipientRepository->create($recipientBatch, $context);
        }
    }
}
