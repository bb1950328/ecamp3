<?php

namespace App\Tests\Api\ContentNodes\SingleText;

use App\Entity\ContentNode\SingleText;
use App\Tests\Api\ContentNodes\CreateContentNodeTestCase;

/**
 * @internal
 */
class CreateSingleTextTest extends CreateContentNodeTestCase {
    public function setUp(): void {
        parent::setUp();

        $this->endpoint = 'single_texts';
        $this->contentNodeClass = SingleText::class;
        $this->defaultContentType = static::$fixtures['contentTypeNotes'];
    }

    public function testCreateSingleTextFromString() {
        // given
        $text = 'TestText';

        // when
        $this->create($this->getExampleWritePayload(['text' => $text]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['text' => $text]);
    }

    public function testCreateSingleTextFromNull() {
        // when
        $this->create($this->getExampleWritePayload(['text' => null]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['text' => null]);
    }

    public function testCreateSingleTextCleansHTMLFromText() {
        // given
        $text = ' testText<script>alert(1)</script>';

        // when
        $this->create($this->getExampleWritePayload(['text' => $text]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'text' => ' testText',
        ]);
    }

    public function testCreateSingleTextFromPrototype() {
        // given
        $prototype = static::$fixtures['singleText2'];

        // when
        $this->create($this->getExampleWritePayload(['prototype' => $this->getIriFor('singleText2')]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['text' => $prototype->text]);
    }
}
