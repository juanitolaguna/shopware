<?php declare(strict_types=1);

namespace Shopware\Api\Tax\Repository;

use Shopware\Api\Entity\Read\EntityReaderInterface;
use Shopware\Api\Entity\RepositoryInterface;
use Shopware\Api\Entity\Search\AggregatorResult;
use Shopware\Api\Entity\Search\Criteria;
use Shopware\Api\Entity\Search\EntityAggregatorInterface;
use Shopware\Api\Entity\Search\EntitySearcherInterface;
use Shopware\Api\Entity\Search\IdSearchResult;
use Shopware\Api\Entity\Write\GenericWrittenEvent;
use Shopware\Api\Entity\Write\WriteContext;
use Shopware\Api\Tax\Collection\TaxAreaRuleTranslationBasicCollection;
use Shopware\Api\Tax\Collection\TaxAreaRuleTranslationDetailCollection;
use Shopware\Api\Tax\Definition\TaxAreaRuleTranslationDefinition;
use Shopware\Api\Tax\Event\TaxAreaRuleTranslation\TaxAreaRuleTranslationAggregationResultLoadedEvent;
use Shopware\Api\Tax\Event\TaxAreaRuleTranslation\TaxAreaRuleTranslationBasicLoadedEvent;
use Shopware\Api\Tax\Event\TaxAreaRuleTranslation\TaxAreaRuleTranslationDetailLoadedEvent;
use Shopware\Api\Tax\Event\TaxAreaRuleTranslation\TaxAreaRuleTranslationIdSearchResultLoadedEvent;
use Shopware\Api\Tax\Event\TaxAreaRuleTranslation\TaxAreaRuleTranslationSearchResultLoadedEvent;
use Shopware\Api\Tax\Struct\TaxAreaRuleTranslationSearchResult;
use Shopware\Context\Struct\ShopContext;
use Shopware\Version\VersionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaxAreaRuleTranslationRepository implements RepositoryInterface
{
    /**
     * @var EntityReaderInterface
     */
    private $reader;

    /**
     * @var EntitySearcherInterface
     */
    private $searcher;

    /**
     * @var EntityAggregatorInterface
     */
    private $aggregator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var VersionManager
     */
    private $versionManager;

    public function __construct(
       EntityReaderInterface $reader,
       VersionManager $versionManager,
       EntitySearcherInterface $searcher,
       EntityAggregatorInterface $aggregator,
       EventDispatcherInterface $eventDispatcher
   ) {
        $this->reader = $reader;
        $this->searcher = $searcher;
        $this->aggregator = $aggregator;
        $this->eventDispatcher = $eventDispatcher;
        $this->versionManager = $versionManager;
    }

    public function search(Criteria $criteria, ShopContext $context): TaxAreaRuleTranslationSearchResult
    {
        $ids = $this->searchIds($criteria, $context);

        $entities = $this->readBasic($ids->getIds(), $context);

        $aggregations = null;
        if ($criteria->getAggregations()) {
            $aggregations = $this->aggregate($criteria, $context);
        }

        $result = TaxAreaRuleTranslationSearchResult::createFromResults($ids, $entities, $aggregations);

        $event = new TaxAreaRuleTranslationSearchResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function aggregate(Criteria $criteria, ShopContext $context): AggregatorResult
    {
        $result = $this->aggregator->aggregate(TaxAreaRuleTranslationDefinition::class, $criteria, $context);

        $event = new TaxAreaRuleTranslationAggregationResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function searchIds(Criteria $criteria, ShopContext $context): IdSearchResult
    {
        $result = $this->searcher->search(TaxAreaRuleTranslationDefinition::class, $criteria, $context);

        $event = new TaxAreaRuleTranslationIdSearchResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function readBasic(array $ids, ShopContext $context): TaxAreaRuleTranslationBasicCollection
    {
        /** @var TaxAreaRuleTranslationBasicCollection $entities */
        $entities = $this->reader->readBasic(TaxAreaRuleTranslationDefinition::class, $ids, $context);

        $event = new TaxAreaRuleTranslationBasicLoadedEvent($entities, $context);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $entities;
    }

    public function readDetail(array $ids, ShopContext $context): TaxAreaRuleTranslationDetailCollection
    {
        /** @var TaxAreaRuleTranslationDetailCollection $entities */
        $entities = $this->reader->readDetail(TaxAreaRuleTranslationDefinition::class, $ids, $context);

        $event = new TaxAreaRuleTranslationDetailLoadedEvent($entities, $context);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $entities;
    }

    public function update(array $data, ShopContext $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->update(TaxAreaRuleTranslationDefinition::class, $data, WriteContext::createFromShopContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function upsert(array $data, ShopContext $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->upsert(TaxAreaRuleTranslationDefinition::class, $data, WriteContext::createFromShopContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function create(array $data, ShopContext $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->insert(TaxAreaRuleTranslationDefinition::class, $data, WriteContext::createFromShopContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function delete(array $ids, ShopContext $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->delete(TaxAreaRuleTranslationDefinition::class, $ids, WriteContext::createFromShopContext($context));
        $event = GenericWrittenEvent::createWithDeletedEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function createVersion(string $id, ShopContext $context, ?string $name = null, ?string $versionId = null): string
    {
        return $this->versionManager->createVersion(TaxAreaRuleTranslationDefinition::class, $id, WriteContext::createFromShopContext($context), $name, $versionId);
    }

    public function merge(string $versionId, ShopContext $context): void
    {
        $this->versionManager->merge($versionId, WriteContext::createFromShopContext($context));
    }
}
