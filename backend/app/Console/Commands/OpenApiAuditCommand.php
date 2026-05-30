<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OpenApiAuditCommand extends Command
{
    protected $signature = 'openapi:audit
                            {--spec= : Path to OpenAPI YAML (default: repo docs/api.openapi.yaml)}
                            {--fail-on-drift : Exit 1 when live routes are missing from the spec}
                            {--write-missing : Append stub path entries for routes missing from the spec}';

    protected $description = 'Compare docs/api.openapi.yaml paths with registered Laravel API routes.';

    public function handle(): int
    {
        $specPath = $this->option('spec')
            ?: base_path('../docs/api.openapi.yaml');

        if (! is_readable($specPath)) {
            $this->error("OpenAPI spec not readable: {$specPath}");

            return self::FAILURE;
        }

        $specOps = $this->operationsFromSpecFile($specPath);
        $liveOps = $this->operationsFromRoutes();

        $missingInSpec = $this->diffOperations($liveOps, $specOps);
        $extraInSpec = $this->diffOperations($specOps, $liveOps);

        $this->info('FIL OpenAPI route audit');
        $this->line("Spec: {$specPath}");
        $this->newLine();
        $this->line('Live route operations: '.count($liveOps));
        $this->line('Spec operations: '.count($specOps));
        $this->line('Missing from spec: '.count($missingInSpec));
        $this->line('Extra in spec (not in routes): '.count($extraInSpec));

        if ($missingInSpec !== []) {
            $this->newLine();
            $this->warn('Missing from OpenAPI spec:');
            foreach ($missingInSpec as $op) {
                $this->line("  {$op}");
            }
        }

        if ($extraInSpec !== []) {
            $this->newLine();
            $this->comment('Documented in spec but no matching live route:');
            foreach ($extraInSpec as $op) {
                $this->line("  {$op}");
            }
        }

        if ($this->option('write-missing') && $missingInSpec !== []) {
            $written = $this->writeMissingPaths($specPath, $missingInSpec);
            $this->newLine();
            $this->info("Appended {$written} stub path(s) to {$specPath}");
            $this->line('Re-run openapi:audit to verify.');
        }

        if ($missingInSpec === [] && $extraInSpec === []) {
            $this->newLine();
            $this->info('OpenAPI spec matches live API routes.');

            return self::SUCCESS;
        }

        if ($this->option('fail-on-drift') && $missingInSpec !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string> operation keys: "METHOD /path"
     */
    private function operationsFromRoutes(): array
    {
        $ops = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $path = '/'.substr($uri, 4);
            $path = preg_replace('/\{[^}]+\}/', '{}', $path) ?? $path;

            foreach ($route->methods() as $method) {
                $upper = strtoupper($method);

                if (in_array($upper, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $ops[] = $upper.' '.$path;
            }
        }

        sort($ops);

        return array_values(array_unique($ops));
    }

    /**
     * @return list<string>
     */
    private function operationsFromSpecFile(string $specPath): array
    {
        $contents = file_get_contents($specPath);

        if ($contents === false) {
            return [];
        }

        $ops = [];
        $currentPath = null;

        foreach (explode("\n", $contents) as $line) {
            if (preg_match('/^  (\/[^\s]+):\s*$/', $line, $matches)) {
                $currentPath = preg_replace('/\{[^}]+\}/', '{}', $matches[1]) ?? $matches[1];

                continue;
            }

            if ($currentPath !== null && preg_match('/^    (get|post|put|patch|delete):\s*$/', $line, $matches)) {
                $ops[] = strtoupper($matches[1]).' '.$currentPath;
            }
        }

        sort($ops);

        return array_values(array_unique($ops));
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     * @return list<string>
     */
    private function diffOperations(array $left, array $right): array
    {
        return array_values(array_diff($left, $right));
    }

    /**
     * @param  list<string>  $missingOps
     */
    private function writeMissingPaths(string $specPath, array $missingOps): int
    {
        $catalog = $this->routeCatalog();
        $byPath = [];

        foreach ($missingOps as $op) {
            [$method, $normalizedPath] = explode(' ', $op, 2);
            $entry = $catalog[$op] ?? null;

            if ($entry === null) {
                continue;
            }

            $byPath[$entry['path']][$method] = $entry['summary'];
        }

        if ($byPath === []) {
            return 0;
        }

        $contents = file_get_contents($specPath);

        if ($contents === false) {
            return 0;
        }

        foreach (array_keys($byPath) as $path) {
            if (str_contains($contents, "  {$path}:\n")) {
                unset($byPath[$path]);
            }
        }

        if ($byPath === []) {
            return 0;
        }

        ksort($byPath);

        $yaml = "\n";
        foreach ($byPath as $path => $methods) {
            $yaml .= "  {$path}:\n";
            ksort($methods);
            foreach ($methods as $method => $summary) {
                $yaml .= '    '.strtolower($method).":\n";
                $yaml .= '      summary: '.json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
            }
        }

        if ($yaml === "\n") {
            return 0;
        }

        $needle = "\ncomponents:";
        $pos = strpos($contents, $needle);

        if ($pos === false) {
            $contents .= $yaml;
        } else {
            $contents = substr($contents, 0, $pos).$yaml.substr($contents, $pos);
        }

        file_put_contents($specPath, $contents);

        return count($byPath);
    }

    /**
     * @return array<string, array{path: string, summary: string}>
     */
    private function routeCatalog(): array
    {
        $catalog = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $path = '/'.substr($uri, 4);
            $normalized = preg_replace('/\{[^}]+\}/', '{}', $path) ?? $path;
            $summary = $this->summaryFromRoute($route);

            foreach ($route->methods() as $method) {
                $upper = strtoupper($method);

                if (in_array($upper, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $catalog[$upper.' '.$normalized] = [
                    'path' => $path,
                    'summary' => $summary,
                ];
            }
        }

        return $catalog;
    }

    private function summaryFromRoute(\Illuminate\Routing\Route $route): string
    {
        $name = (string) $route->getName();

        if ($name !== '') {
            $label = str_replace(['api.', 'api.v1.', '.'], ['', '', ' '], $name);

            return Str::headline($label);
        }

        $methods = implode('|', array_diff($route->methods(), ['HEAD']));
        $uri = $route->uri();

        return Str::headline("{$methods} api/{$uri}");
    }
}
