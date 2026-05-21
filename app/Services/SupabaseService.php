<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * SupabaseService
 *
 * Wrapper ligero para la API REST de Supabase (PostgREST).
 * Usa la service-role key para operaciones privilegiadas desde el backend.
 *
 * Ejemplo de uso desde un controlador:
 *
 *   $svc = new SupabaseService();
 *   $rows = $svc->from('alimentos')->select('*')->get();
 */
class SupabaseService
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $table  = '';
    protected string $select = '*';
    protected array  $filters = [];
    protected ?int   $limitVal  = null;
    protected ?int   $offsetVal = null;

    public function __construct()
    {
        $this->baseUrl    = rtrim(config('supabase.url'), '/') . '/rest/v1';
        $this->serviceKey = config('supabase.service_role_key');
    }

    // ── Builder ────────────────────────────────────────────────────────────

    public function from(string $table): static
    {
        $clone = clone $this;
        $clone->table   = $table;
        $clone->filters = [];
        $clone->select  = '*';
        return $clone;
    }

    public function select(string $columns): static
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        // PostgREST filter syntax: column=op.value
        $map = ['=' => 'eq', '!=' => 'neq', '>' => 'gt', '>=' => 'gte', '<' => 'lt', '<=' => 'lte'];
        $op  = $map[$operator] ?? $operator;
        $this->filters[$column] = "{$op}.{$value}";
        return $this;
    }

    public function limit(int $n): static
    {
        $this->limitVal = $n;
        return $this;
    }

    public function offset(int $n): static
    {
        $this->offsetVal = $n;
        return $this;
    }

    // ── Ejecutores ─────────────────────────────────────────────────────────

    /** GET – devuelve array de filas */
    public function get(): array
    {
        $query = array_merge(['select' => $this->select], $this->filters);
        if ($this->limitVal  !== null) $query['limit']  = $this->limitVal;
        if ($this->offsetVal !== null) $query['offset'] = $this->offsetVal;

        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/{$this->table}", $query);

        $response->throw();
        return $response->json();
    }

    /** GET – devuelve primera fila o null */
    public function first(): ?array
    {
        $rows = $this->limit(1)->get();
        return $rows[0] ?? null;
    }

    /** POST – inserta una fila, devuelve el registro creado */
    public function insert(array $data): array
    {
        $response = Http::withHeaders(array_merge($this->headers(), [
            'Prefer' => 'return=representation',
        ]))->post("{$this->baseUrl}/{$this->table}", $data);

        $response->throw();
        return $response->json()[0] ?? $response->json();
    }

    /** PATCH – actualiza filas que cumplan los filtros */
    public function update(array $data): array
    {
        $response = Http::withHeaders(array_merge($this->headers(), [
            'Prefer' => 'return=representation',
        ]))->patch("{$this->baseUrl}/{$this->table}", $data, $this->filters);

        $response->throw();
        return $response->json();
    }

    /** DELETE – elimina filas que cumplan los filtros */
    public function delete(): void
    {
        Http::withHeaders($this->headers())
            ->delete("{$this->baseUrl}/{$this->table}", $this->filters)
            ->throw();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function headers(): array
    {
        return [
            'apikey'        => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type'  => 'application/json',
        ];
    }
}
