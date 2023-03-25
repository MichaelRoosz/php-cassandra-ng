<?php

declare(strict_types=1);

namespace Cassandra\Response;

use Cassandra\Protocol\Frame;
use Cassandra\Response\StreamReader;
use Stringable;

abstract class Response implements Frame, Stringable {
    /**
     * @var array{
     *  version: int,
     *  flags: int,
     *  stream: int,
     *  opcode: int,
     * } $header
     */
    protected array $header;

    /**
     * @var ?array<string,?string> $payload
     */
    protected ?array $payload = null;

    protected StreamReader $stream;

    protected ?string $tracingUuid = null;

    /**
     * @var ?array<string> $warnings
     */
    protected ?array $warnings = null;

    /**
     * @param array{
     *  version: int,
     *  flags: int,
     *  stream: int,
     *  opcode: int,
     * } $header
     *
     * @throws \Cassandra\Response\Exception
     */
    final public function __construct(array $header, StreamReader $stream) {
        $this->header = $header;

        $this->stream = $stream;

        $this->readExtraData();
    }

    public function __toString(): string {
        $body = $this->getBody();

        return pack(
            'CCnCN',
            $this->header['version'],
            $this->header['flags'],
            $this->header['stream'],
            $this->header['opcode'],
            strlen($body)
        ) . $body;
    }

    public function getBody(): string {
        return $this->stream->getData();
    }

    public function getBodyStreamReader(): StreamReader {
        return $this->stream;
    }

    public function getFlags(): int {
        return $this->header['flags'];
    }

    public function getOpcode(): int {
        return $this->header['opcode'];
    }

    /**
     * @return ?array<string,?string>
     */
    public function getPayload(): ?array {
        return $this->payload;
    }

    public function getStream(): int {
        return $this->header['stream'];
    }

    public function getTracingUuid(): ?string {
        return $this->tracingUuid;
    }

    public function getVersion(): int {
        return $this->header['version'];
    }

    /**
     * @return ?array<string>
     */
    public function getWarnings(): ?array {
        return $this->warnings;
    }

    /**
     * @throws \Cassandra\Response\Exception
     */
    protected function readExtraData(): void {
        $flags = $this->header['flags'];

        if ($flags & self::FLAG_TRACING) {
            $this->tracingUuid = $this->stream->readUuid();
        }

        if ($flags & self::FLAG_WARNING) {
            $this->warnings = $this->stream->readStringList();
        }

        if ($flags & self::FLAG_CUSTOM_PAYLOAD) {
            $this->payload = $this->stream->readBytesMap();
        }

        $this->stream->extraDataOffset($this->stream->pos());
    }
}
