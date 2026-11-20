<?php

use App\Services\McpToken;

beforeEach(function () {
    $this->initialize = [
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'initialize',
        'params'  => [
            'protocolVersion' => '2025-06-18',
            'capabilities'    => (object) [],
            'clientInfo'      => ['name' => 'test', 'version' => '1.0'],
        ],
    ];

    $this->callMcp = fn (?string $token) => $this->postJson(
        '/mcp',
        $this->initialize,
        $token !== null ? ['Authorization' => "Bearer {$token}"] : [],
    );
});

describe('MCP endpoint authentication', function () {
    it('rejects requests when no token has been generated', function () {
        ($this->callMcp)('anything')->assertUnauthorized();
    });

    it('rejects requests without a bearer token', function () {
        app(McpToken::class)->generate();

        ($this->callMcp)(null)->assertUnauthorized();
    });

    it('rejects requests with a wrong token', function () {
        app(McpToken::class)->generate();

        ($this->callMcp)('wrong')->assertUnauthorized();
    });

    it('accepts requests with the generated token', function () {
        $token = app(McpToken::class)->generate();

        ($this->callMcp)($token)
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'Argoos');
    });

    it('rejects the old token immediately after regeneration', function () {
        $tokens = app(McpToken::class);
        $old    = $tokens->generate();
        ($this->callMcp)($old)->assertOk();

        $new = $tokens->generate();

        ($this->callMcp)($old)->assertUnauthorized();
        ($this->callMcp)($new)->assertOk();
    });

    it('rejects the token immediately after revocation', function () {
        $tokens = app(McpToken::class);
        $token  = $tokens->generate();
        ($this->callMcp)($token)->assertOk();

        $tokens->revoke();

        ($this->callMcp)($token)->assertUnauthorized();
    });
});
