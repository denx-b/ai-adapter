<?php

declare(strict_types=1);

namespace AiAdapter\Core;

use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\Message;

final class PromptBuilder
{
    /**
     * @return list<Message>
     */
    public static function build(ChatRequest $request, bool $injectJsonInstruction = false): array
    {
        $messages = $request->messages();

        $context = $request->contextData();
        if ($context !== []) {
            $messages[] = Message::system(
                "Context (JSON):\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        }

        $files = $request->filesData();
        if ($files !== []) {
            $parts = [];
            foreach ($files as $file) {
                $parts[] = "File: {$file->name()}\n" . $file->content();
            }
            $messages[] = Message::system("Attached files:\n\n" . implode("\n\n---\n\n", $parts));
        }

        if ($injectJsonInstruction && $request->outputFormat()?->isJsonSchema()) {
            $schema = $request->outputFormat()?->schema() ?? [];
            $messages[] = Message::system(
                "Return only valid JSON matching this schema:\n" .
                json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        }

        return $messages;
    }
}
