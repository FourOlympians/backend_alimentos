/**
 * useApi.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Composable Vue 3 para consumir la API Laravel de FoodLight.
 *
 * Copia este archivo a src/composables/useApi.js en tu proyecto Vue.
 *
 * Requiere en .env del proyecto Vue:
 *   VITE_API_URL=http://localhost:8000/api
 *
 * Supabase Auth ya gestiona el login/logout.
 * El token JWT de Supabase se envía automáticamente en cada petición protegida.
 * ─────────────────────────────────────────────────────────────────────────────
 */

import { ref } from 'vue'
import { createClient } from '@supabase/supabase-js'

// ── Cliente Supabase (para obtener el token de sesión) ─────────────────────
const supabase = createClient(
  import.meta.env.VITE_SUPABASE_URL,
  import.meta.env.VITE_SUPABASE_ANON_KEY
)

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

// ── Helper base ────────────────────────────────────────────────────────────

async function apiFetch(path, options = {}) {
  const { data: sessionData } = await supabase.auth.getSession()
  const token = sessionData?.session?.access_token

  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const res = await fetch(`${API_BASE}${path}`, { ...options, headers })

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: res.statusText }))
    throw new Error(err.error ?? `HTTP ${res.status}`)
  }

  return res.json()
}

// ══════════════════════════════════════════════════════════════════════════════
// Composable principal
// ══════════════════════════════════════════════════════════════════════════════

export function useApi() {
  const loading = ref(false)
  const error   = ref(null)

  async function run(fn) {
    loading.value = true
    error.value   = null
    try {
      return await fn()
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Alimentos ────────────────────────────────────────────────────────────

  /**
   * Lista paginada de alimentos.
   * @param {{ q?: string, grupo_id?: number, per_page?: number, page?: number }} params
   */
  function getAlimentos(params = {}) {
    const qs = new URLSearchParams(params).toString()
    return run(() => apiFetch(`/alimentos?${qs}`))
  }

  /**
   * Alimentos clasificados por semáforo para las condiciones del usuario.
   * @param {{ condicion_ids?: string, q?: string, color?: string, grupo_id?: number }} params
   */
  function getSemaforo(params = {}) {
    const qs = new URLSearchParams(params).toString()
    return run(() => apiFetch(`/alimentos/semaforo?${qs}`))
  }

  function getAlimento(id) {
    return run(() => apiFetch(`/alimentos/${id}`))
  }

  function getGrupos() {
    return run(() => apiFetch('/grupos'))
  }

  // ── Condiciones ──────────────────────────────────────────────────────────

  function getCondiciones() {
    return run(() => apiFetch('/condiciones'))
  }

  // ── Recetas ──────────────────────────────────────────────────────────────

  /**
   * @param {{ q?: string, per_page?: number }} params
   */
  function getRecetas(params = {}) {
    const qs = new URLSearchParams(params).toString()
    return run(() => apiFetch(`/recetas?${qs}`))
  }

  function getReceta(id) {
    return run(() => apiFetch(`/recetas/${id}`))
  }

  /**
   * Recetas aptas para las condiciones del usuario.
   * @param {{ condicion_ids?: string, tiempo_max?: number }} params
   */
  function getRecetasParaMi(params = {}) {
    const qs = new URLSearchParams(params).toString()
    return run(() => apiFetch(`/recetas/para-mi?${qs}`))
  }

  // ── Perfil (protegido) ────────────────────────────────────────────────────

  function getPerfil() {
    return run(() => apiFetch('/perfil'))
  }

  /**
   * @param {{ nombre?: string, fecha_nacimiento?: string, sexo?: string,
   *            peso_kg?: number, talla_cm?: number }} data
   */
  function updatePerfil(data) {
    return run(() => apiFetch('/perfil', { method: 'PUT', body: JSON.stringify(data) }))
  }

  /**
   * Sincroniza las condiciones activas del usuario.
   * @param {number[]} condicionIds
   */
  function syncCondiciones(condicionIds) {
    return run(() => apiFetch('/perfil/condiciones', {
      method: 'POST',
      body: JSON.stringify({ condicion_ids: condicionIds }),
    }))
  }

  // ── Health-check ──────────────────────────────────────────────────────────

  function healthCheck() {
    return run(() => apiFetch('/health'))
  }

  return {
    loading,
    error,
    // alimentos
    getAlimentos,
    getSemaforo,
    getAlimento,
    getGrupos,
    // condiciones
    getCondiciones,
    // recetas
    getRecetas,
    getReceta,
    getRecetasParaMi,
    // perfil
    getPerfil,
    updatePerfil,
    syncCondiciones,
    // util
    healthCheck,
  }
}
