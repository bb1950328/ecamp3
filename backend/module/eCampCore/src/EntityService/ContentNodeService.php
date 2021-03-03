<?php

namespace eCamp\Core\EntityService;

use Doctrine\ORM\ORMException;
use eCamp\Core\ContentType\ContentTypeStrategyProvider;
use eCamp\Core\Entity\AbstractContentNodeOwner;
use eCamp\Core\Entity\ContentNode;
use eCamp\Core\Entity\ContentType;
use eCamp\Core\Hydrator\ContentNodeHydrator;
use eCamp\Lib\Acl\NoAccessException;
use eCamp\Lib\Entity\BaseEntity;
use eCamp\Lib\Service\ServiceUtils;
use Laminas\Authentication\AuthenticationService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContentNodeService extends AbstractEntityService {
    private ContentTypeStrategyProvider $contentTypeStrategyProvider;

    public function __construct(
        ServiceUtils $serviceUtils,
        AuthenticationService $authenticationService,
        ContentTypeStrategyProvider $contentTypeStrategyProvider
    ) {
        parent::__construct(
            $serviceUtils,
            ContentNode::class,
            ContentNodeHydrator::class,
            $authenticationService
        );

        $this->contentTypeStrategyProvider = $contentTypeStrategyProvider;
    }

    public function createFromPrototype(AbstractContentNodeOwner $owner, ContentNode $prototype): ContentNode {
        /** @var ContentNode $contentNode */
        $contentNode = $this->create((object) [
            'ownerId' => $owner->getId(),
            'contentTypeId' => $prototype->getContentType()->getId(),
            'instanceName' => $prototype->getInstanceName(),
            'slot' => $prototype->getSlot(),
            'position' => $prototype->getPosition(),
            'config' => $prototype->getConfig(),
        ]);

        foreach ($prototype->getMyChildren() as $childPrototype) {
            $childContentNode = $this->createFromPrototype($owner, $childPrototype);
            $childContentNode->setParent($contentNode);
        }

        return $contentNode;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws NoAccessException
     */
    protected function createEntity($data): ContentNode {
        /** @var ContentNode $contentNode */
        $contentNode = parent::createEntity($data);

        /** @var AbstractContentNodeOwner $owner */
        $owner = $this->findRelatedEntity(AbstractContentNodeOwner::class, $data, 'ownerId');

        /** @var ContentType $contentType */
        $contentType = $this->findRelatedEntity(ContentType::class, $data, 'contentTypeId');

        $owner->setRootContentNode($contentNode);
        $contentNode->setContentType($contentType);

        return $contentNode;
    }

    protected function createEntityPost(BaseEntity $entity, $data): BaseEntity {
        /** @var ContentNode $contentNode */
        $contentNode = parent::createEntityPost($entity, $data);

        $strategy = $this->contentTypeStrategyProvider->get($contentNode);
        if (null != $strategy) {
            $strategy->contentNodeCreated($contentNode);
        }

        return $contentNode;
    }

    protected function patchEntity(BaseEntity $entity, $data): ContentNode {
        /** @var ContentNode $contentNode */
        $contentNode = parent::patchEntity($entity, $data);

        if (isset($data->parentId)) {
            /** @var ContentNode $parent */
            $parent = $this->findRelatedEntity(ContentNode::class, $data, 'parentId');
            $contentNode->setParent($parent);
        }

        return $entity;
    }
}
