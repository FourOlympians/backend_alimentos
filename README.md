# FoodLight · API Laravel 11 + Supabase

Backend REST para la app Vue FoodLight. Conecta directamente a la base de datos
PostgreSQL de Supabase y valida los tokens JWT que emite Supabase Auth.

---

## Estructura de archivos

```
foodlight-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AlimentoController.php   ← alimentos + semáforo
│   │   │   ├── CondicionController.php  ← catálogo de condiciones
│   │   │   ├── PerfilController.php     ← perfil del usuario (protegido)
│   │   │   └── RecetaController.php     ← recetas
│   │   └── Middleware/
│   │       └── VerifySupabaseJwt.php    ← valida Bearer token de Supabase
│   ├── Models/
│   │   ├── Alimento.php
│   │   ├── GrupoAlimento.php
│   │   └── Models.php                  ← Receta, RecetaIngrediente,
│   │                                      CondicionMedica, ClasificacionCache,
│   │                                      ReglaSemaforo
│   └── Services/
│       ├── SemaforoService.php          ← lógica de clasificación verde/amarillo/rojo
│       └── SupabaseService.php          ← cliente HTTP para la API REST de Supabase
├── config/
│   ├── cors.php
│   └── supabase.php
├── database/
│   ├── migrations/
│   │   └── 2024_01_01_create_foodlight_tables.php
│   └── seeders/
│       └── DatabaseSeeder.php           ← condiciones + reglas semáforo
├── routes/
│   └── api.php
├── bootstrap/
│   └── app.php
├── frontend-composable/
│   └── useApi.js                        ← copia esto a tu proyecto Vue
└── .env.example
```

---

## Instalación

```bash
# 1. Instalar dependencias
composer install

# 2. Copiar y rellenar variables de entorno
cp .env.example .env
# → edita DB_PASSWORD, SUPABASE_*, FRONTEND_URL

# 3. Generar clave de la app
php artisan key:generate

# 4. Correr migraciones (conecta directo a Supabase)
php artisan migrate

# 5. Sembrar condiciones y reglas del semáforo
php artisan db:seed

# 6. Iniciar servidor
php artisan serve
```

---

## Endpoints

| Método | Ruta                        | Auth | Descripción                                   |
|--------|-----------------------------|------|-----------------------------------------------|
| GET    | /api/health                 | –    | Estado de la API                              |
| GET    | /api/grupos                 | –    | Grupos de alimentos                           |
| GET    | /api/condiciones            | –    | Catálogo de condiciones médicas               |
| GET    | /api/alimentos              | –    | Lista paginada (`q`, `grupo_id`, `per_page`)  |
| GET    | /api/alimentos/{id}         | –    | Detalle de un alimento                        |
| GET    | /api/alimentos/semaforo     | –    | Lista con color (`condicion_ids`, `color`)    |
| GET    | /api/recetas                | –    | Lista paginada de recetas                     |
| GET    | /api/recetas/{id}           | –    | Detalle con ingredientes y condiciones        |
| GET    | /api/recetas/para-mi        | –    | Recetas aptas para condiciones dadas          |
| GET    | /api/perfil                 | JWT  | Perfil + condiciones del usuario              |
| PUT    | /api/perfil                 | JWT  | Actualizar datos del perfil                   |
| POST   | /api/perfil/condiciones     | JWT  | Sincronizar condiciones activas               |

### Ejemplo: semáforo

```
GET /api/alimentos/semaforo?condicion_ids=1,2&q=pan&color=rojo
```

Respuesta:
```json
{
  "data": [
    { "id": 42, "nombre": "Pan blanco", "color": "rojo", "grupo_nombre": "Cereales", ... }
  ],
  "totals": { "verde": 120, "amarillo": 45, "rojo": 12 }
}
```

---

## Conexión desde Vue

Copia `frontend-composable/useApi.js` a tu proyecto Vue y agrega al `.env`:

```
VITE_API_URL=http://localhost:8000/api
VITE_SUPABASE_URL=https://pdqtptokxanlmllmmlid.supabase.co
VITE_SUPABASE_ANON_KEY=sb_publishable_...
```

Uso en un componente:

```vue
<script setup>
import { onMounted, ref } from 'vue'
import { useApi } from '@/composables/useApi'

const { getSemaforo, loading } = useApi()
const alimentos = ref([])

onMounted(async () => {
  const res = await getSemaforo({ condicion_ids: '1,2' })
  alimentos.value = res.data
})
</script>
```

---

## Autenticación

El middleware `supabase.jwt` valida el token Bearer emitido por Supabase Auth usando
`SUPABASE_JWT_SECRET`. El frontend envía el token así:

```
Authorization: Bearer <supabase_access_token>
```

El composable `useApi.js` lo hace automáticamente leyendo la sesión activa de Supabase.

---

## Variables de entorno clave

| Variable                | Dónde encontrarla                          |
|-------------------------|--------------------------------------------|
| `DB_HOST`               | Supabase → Settings → Database → Host      |
| `DB_PASSWORD`           | Supabase → Settings → Database → Password  |
| `SUPABASE_ANON_KEY`     | Supabase → Settings → API → anon key       |
| `SUPABASE_SERVICE_ROLE_KEY` | Supabase → Settings → API → service_role |
| `SUPABASE_JWT_SECRET`   | Supabase → Settings → API → JWT Secret     |
