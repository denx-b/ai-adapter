<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

use AiAdapter\Exception\ValidationException;

final class ChatRequest
{
    /**
     * @param list<Message> $messages
     * @param array<string, mixed> $context
     * @param list<FilePart> $files
     */
    private function __construct(
        private readonly array $messages = [],
        private readonly array $context = [],
        private readonly array $files = [],
        private readonly ?OutputFormat $output = null,
        private readonly ?float $temperature = null,
        private readonly ?string $provider = null,
        private readonly ?string $model = null,
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public function system(string $content): self
    {
        return $this->message(Message::system($content));
    }

    public function user(string $content): self
    {
        return $this->message(Message::user($content));
    }

    public function assistant(string $content): self
    {
        return $this->message(Message::assistant($content));
    }

    public function message(Message $message): self
    {
        $messages = $this->messages;
        $messages[] = $message;

        return new self(
            $messages,
            $this->context,
            $this->files,
            $this->output,
            $this->temperature,
            $this->provider,
            $this->model,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function context(array $context): self
    {
        return new self(
            $this->messages,
            array_merge($this->context, $context),
            $this->files,
            $this->output,
            $this->temperature,
            $this->provider,
            $this->model,
        );
    }

    /**
     * @param list<FilePart> $files
     */
    public function files(array $files): self
    {
        foreach ($files as $file) {
            if (!$file instanceof FilePart) {
                throw new ValidationException('files() expects array of FilePart.');
            }
        }

        return new self(
            $this->messages,
            $this->context,
            $files,
            $this->output,
            $this->temperature,
            $this->provider,
            $this->model,
        );
    }

    public function addFile(FilePart $file): self
    {
        $files = $this->files;
        $files[] = $file;

        return $this->files($files);
    }

    public function output(OutputFormat $output): self
    {
        return new self(
            $this->messages,
            $this->context,
            $this->files,
            $output,
            $this->temperature,
            $this->provider,
            $this->model,
        );
    }

    public function temperature(float $temperature): self
    {
        return new self(
            $this->messages,
            $this->context,
            $this->files,
            $this->output,
            $temperature,
            $this->provider,
            $this->model,
        );
    }

    public function provider(string $provider): self
    {
        return new self(
            $this->messages,
            $this->context,
            $this->files,
            $this->output,
            $this->temperature,
            $provider,
            $this->model,
        );
    }

    public function model(string $model): self
    {
        return new self(
            $this->messages,
            $this->context,
            $this->files,
            $this->output,
            $this->temperature,
            $this->provider,
            $model,
        );
    }

    public function target(string $provider, string $model): self
    {
        return $this->provider($provider)->model($model);
    }

    /**
     * @return list<Message>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return array<string, mixed>
     */
    public function contextData(): array
    {
        return $this->context;
    }

    /**
     * @return list<FilePart>
     */
    public function filesData(): array
    {
        return $this->files;
    }

    public function outputFormat(): ?OutputFormat
    {
        return $this->output;
    }

    public function temperatureValue(): ?float
    {
        return $this->temperature;
    }

    public function providerName(): ?string
    {
        return $this->provider;
    }

    public function modelName(): ?string
    {
        return $this->model;
    }
}
