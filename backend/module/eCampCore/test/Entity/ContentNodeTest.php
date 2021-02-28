<?php

namespace eCamp\CoreTest\Entity;

use eCamp\Core\Entity\Activity;
use eCamp\Core\Entity\Camp;
use eCamp\Core\Entity\ContentNode;
use eCamp\Core\Entity\ContentType;
use eCamp\LibTest\PHPUnit\AbstractTestCase;

/**
 * @internal
 */
class ContentNodeTest extends AbstractTestCase {
    public function testContentNode(): void {
        $camp = new Camp();

        $contentType = new ContentType();

        $activity = new Activity();
        $activity->setCamp($camp);
        $activity->setTitle('ActivityTitle');

        $contentNode = new ContentNode();
        $contentNode->setOwner($activity);
        $contentNode->setContentType($contentType);
        $contentNode->setInstanceName('ContentNodeName');
        $contentNode->setSlot('slot');
        $contentNode->setPosition(1);

        $this->assertEquals($activity, $contentNode->getOwner());
        $this->assertEquals($contentType, $contentNode->getContentType());
        $this->assertEquals('ContentNodeName', $contentNode->getInstanceName());
        $this->assertEquals('slot', $contentNode->getSlot());
        $this->assertEquals(1, $contentNode->getPosition());
        $this->assertEquals($camp, $contentNode->getCamp());
    }

    public function testContentNodeHierarchy(): void {
        $contentNode = new ContentNode();
        $childContentNode = new ContentNode();

        // Add Child-ContentNode
        $contentNode->addChild($childContentNode);
        $this->assertCount(1, $contentNode->getChildren());
        $this->assertEquals($contentNode, $childContentNode->getParent());

        // Remove Child-ContentNode
        $contentNode->removeChild($childContentNode);
        $this->assertCount(0, $contentNode->getChildren());
        $this->assertNull($childContentNode->getParent());
    }
}
