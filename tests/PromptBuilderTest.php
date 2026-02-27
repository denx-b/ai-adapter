<?php

declare(strict_types=1);

namespace AiAdapter\Tests;

use AiAdapter\Core\PromptBuilder;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\OutputFormat;
use PHPUnit\Framework\TestCase;

final class PromptBuilderTest extends TestCase
{
    public function testDefaultOutputIsTextAndNoJsonInstructionInjected(): void
    {
        $request = ChatRequest::make()
            ->system('System message')
            ->user('User message');

        self::assertNull($request->outputFormat());

        $messages = PromptBuilder::build($request, true);

        self::assertCount(2, $messages);
        self::assertSame('system', $messages[0]->role());
        self::assertSame('System message', $messages[0]->content());
        self::assertSame('user', $messages[1]->role());
        self::assertSame('User message', $messages[1]->content());
    }

    public function testJsonSchemaOutputInjectsInstruction(): void
    {
        $request = ChatRequest::make()
            ->user('Return structured output')
            ->output(OutputFormat::jsonSchema([
                'type' => 'object',
                'properties' => [
                    'answer' => ['type' => 'string'],
                ],
                'required' => ['answer'],
            ]));

        $messages = PromptBuilder::build($request, true);

        self::assertCount(2, $messages);
        self::assertSame('system', $messages[1]->role());
        self::assertStringContainsString('Return only valid JSON matching this schema', $messages[1]->content());
        self::assertStringContainsString('"answer"', $messages[1]->content());
    }
}
