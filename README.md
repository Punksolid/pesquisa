# Pesquisa

Pesquisa es una plataforma colaborativa de investigación expuesta vía **MCP (Model
Context Protocol)**. Los casos (reales o demo) se estructuran como una mecánica de
juego: se aporta evidencia, se proponen hipótesis sobre la causa raíz, se votan, y
los investigadores acumulan puntos.

Cualquier cliente MCP (Claude, Claude Code, Cursor, etc.) puede conectarse al
servidor, autenticarse vía OAuth 2.1, y jugar: listar investigaciones abiertas,
leer el dossier completo de un caso, aportar evidencia, proponer y votar
hipótesis, y confirmar la causa raíz cuando el caso se resuelve.

## Dominio

| Modelo | Descripción |
|---|---|
| `Investigation` | Un caso abierto, con título, resumen y estado (`open`, `solved`, `archived`). |
| `Evidence` | Una pieza de evidencia aportada por un usuario a una investigación. |
| `Hypothesis` | Una hipótesis de causa raíz propuesta por un usuario. |
| `HypothesisVote` | El voto de confianza (1-5) de un usuario sobre una hipótesis. |
| `User` | Además de autenticación, acumula `points` por participar. |

### Mecánica de puntos (`App\Support\Points`)

| Acción | Puntos |
|---|---|
| Aportar evidencia | +5 |
| Proponer una hipótesis | +10 |
| Votar una hipótesis | +1 |
| Tu hipótesis es confirmada como causa raíz | +50 |
| Votaste por la hipótesis que resultó ganadora | +15 |

## El servidor MCP

`app/Mcp/Servers/PesquisaServer.php` expone:

**7 tools**
- `list-investigations-tool` — lista investigaciones, opcionalmente filtradas por estado.
- `view-investigation-tool` — dossier completo de un caso (evidencia, hipótesis, votos).
- `submit-evidence-tool` — aporta evidencia a un caso abierto.
- `propose-hypothesis-tool` — propone una hipótesis de causa raíz.
- `vote-hypothesis-tool` — vota una hipótesis con un nivel de confianza (1-5).
- `confirm-hypothesis-tool` — el creador del caso confirma la causa raíz, cierra el caso y paga el bono.
- `leaderboard-tool` — top investigadores por puntos.

**2 resources**
- `investigations://open` — snapshot en Markdown de todas las investigaciones abiertas.
- `leaderboard://top` — snapshot en Markdown del top 10 del leaderboard.

**1 prompt**
- `investigation-briefing-prompt` — arma un brief para que un LLM revise la evidencia
  y las hipótesis de un caso y razone sobre la causa más probable.

## Autenticación: OAuth 2.1 vía Passport

El servidor requiere un Bearer token válido emitido por Passport (guard `api`).
`routes/ai.php` registra:

```php
Mcp::oauthRoutes();

Mcp::web('/mcp/pesquisa', PesquisaServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
```

`Mcp::oauthRoutes()` expone el descubrimiento OAuth estándar
(`/.well-known/oauth-protected-resource`, `/.well-known/oauth-authorization-server`)
y el registro dinámico de clientes (`POST /oauth/register`, RFC 7591), para que
cualquier cliente MCP (Claude Desktop, Claude Code, etc.) se conecte sin
configuración manual de client_id/secret. Passport aporta `/oauth/authorize` y
`/oauth/token` con soporte de PKCE (S256), obligatorio para clientes públicos
bajo OAuth 2.1.

`User` implementa `Laravel\Passport\Contracts\OAuthenticatable` (vía el trait
`HasApiTokens`), y `config/auth.php` define el guard `api` con driver `passport`.
`AppServiceProvider::boot()` registra `Passport::authorizationView('mcp::authorize')`
(la vista de consentimiento que trae el propio paquete `laravel/mcp`) y el rate
limiter `mcp` usado por el middleware `throttle:mcp`.

Como la pantalla de consentimiento de Passport requiere una sesión web
autenticada, el proyecto incluye un login mínimo (`/login`, guard `web`).
Ese formulario **también sirve como registro**: si el email no existe todavía,
`LoginController@store` crea la cuenta ahí mismo (con el nombre indicado, o el
prefijo del email si se deja en blanco) y loguea al usuario en el mismo paso.
En la práctica esto significa que conectarse por primera vez desde un cliente
MCP —que redirige a `/login` al no encontrar sesión— ya alcanza para darse de
alta: no hace falta un flujo de registro separado.

## Instalación

```bash
composer install
npm install && npm run build

cp .env.example .env   # si no existe ya un .env
php artisan key:generate

# Base de datos (SQLite por defecto)
touch database/database.sqlite
php artisan migrate

# Claves de cifrado de Passport (JWT de los access tokens)
php artisan passport:keys

# Datos demo: usuarios + un caso resuelto + un caso abierto
php artisan db:seed
```

Usuarios demo creados por `DemoInvestigationSeeder` (contraseña `password` para
todos): `ana@pesquisa.test`, `marco@pesquisa.test`, `priya@pesquisa.test`,
`tom@pesquisa.test`, `lucia@pesquisa.test`.

Levantá la app:

```bash
php artisan serve
```

## Verificar que todo compila y migra

```bash
php artisan test    # 11 tests, cubren cada tool + reglas de negocio
./vendor/bin/pint    # estilo de código
php artisan route:list --path=mcp
php artisan route:list --path=oauth
```

## Probar el servidor con el MCP Inspector

```bash
php artisan mcp:inspector mcp/pesquisa
```

Esto levanta `npx @modelcontextprotocol/inspector` apuntando a
`http://127.0.0.1:8000/mcp/pesquisa` (necesitás `php artisan serve` corriendo en
paralelo). El Inspector abre una UI web donde podés completar el login OAuth
(te redirige a `/login`, y luego a la pantalla de consentimiento) y probar cada
tool/resource/prompt interactivamente.

Para probarlo sin UI (headless), el modo `--cli` del Inspector permite scriptear
todo el flujo, incluida la obtención del token:

```bash
npx @modelcontextprotocol/inspector --cli \
  --server-url http://127.0.0.1:8000/mcp/pesquisa \
  --transport http \
  --header "Authorization: Bearer <access_token>" \
  --method tools/list
```

### Flujo OAuth manual (para depurar sin un cliente MCP)

1. **Registro dinámico de cliente** (RFC 7591):
   ```bash
   curl -X POST http://127.0.0.1:8000/oauth/register \
     -H "Content-Type: application/json" \
     -d '{"client_name":"Test Client","redirect_uris":["http://localhost:9999/callback"],"grant_types":["authorization_code","refresh_token"],"response_types":["code"],"token_endpoint_auth_method":"none"}'
   ```
2. Generá un par PKCE (`code_verifier` / `code_challenge` S256), armá la URL de
   `/oauth/authorize` con `client_id`, `redirect_uri`, `scope=mcp:use`,
   `code_challenge`, `code_challenge_method=S256`.
3. Iniciá sesión en `/login` con uno de los usuarios demo, abrí la URL de
   authorize, aprobá el consentimiento.
4. Intercambiá el `code` recibido en el `redirect_uri` por un access token en
   `POST /oauth/token` (con `code_verifier`).
5. Llamá al servidor con `Authorization: Bearer <access_token>`.

Este flujo completo (registro dinámico → PKCE → login → consentimiento →
token → llamada al MCP) fue verificado end-to-end durante el desarrollo.

## Estructura relevante

```
app/Mcp/Servers/PesquisaServer.php     Definición del servidor MCP
app/Mcp/Tools/                          Las 7 tools
app/Mcp/Resources/                      Los 2 resources
app/Mcp/Prompts/                        El prompt
app/Models/                             Investigation, Evidence, Hypothesis, HypothesisVote, User
app/Support/Points.php                  Constantes de la mecánica de puntos
database/seeders/DemoInvestigationSeeder.php
routes/ai.php                           Registro del servidor MCP + rutas OAuth
tests/Feature/Mcp/PesquisaServerTest.php
```
