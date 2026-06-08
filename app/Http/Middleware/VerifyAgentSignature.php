<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $agentId  = $request->header('X-Agent-ID');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (!$agentId || !$timestamp || !$signature) {
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }

        // Reject requests older than 60 seconds to prevent replay attacks
        if (abs(time() - (int) $timestamp) > 60) {
            return response()->json(['error' => 'Request timestamp expired'], 401);
        }

        $agent = Agent::find($agentId);

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 401);
        }

        if ($agent->status === 'revoked') {
            return response()->json(['error' => 'Agent key revoked'], 403);
        }

        // Rebuild canonical string matching Go agent: "METHOD\nPATH\nTIMESTAMP\nHEX(SHA256(body))"
        $body      = $request->getContent();
        $bodyHash  = hash('sha256', $body);
        $canonical = implode("\n", [
            strtoupper($request->method()),
            '/' . $request->path(),
            $timestamp,
            $bodyHash,
        ]);

        $publicKey      = base64_decode($agent->public_key, strict: true);
        $signatureBytes = base64_decode($signature, strict: true);

        if ($publicKey === false || $signatureBytes === false) {
            return response()->json(['error' => 'Invalid key or signature encoding'], 401);
        }

        if (!sodium_crypto_sign_verify_detached($signatureBytes, $canonical, $publicKey)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Attach agent to request for use in controllers
        $request->attributes->set('agent', $agent);

        return $next($request);
    }
}
