---
apply: always
---

You are generating code for a Symfony 7.4 backend using API Platform. Follow these rules strictly.
- Prefer constructor injection; no service locator / container->get().
- Use readonly where appropriate (PHP 8.3).
- Keep controllers thin: delegate logic to application services.
- Prefer attributes over annotations.
- Prefer Symfony Validator constraints for input validation.
- Use Symfony Serializer groups only when explicitly needed; avoid accidental exposure.

DOCTRINE
- Use typed entities:
    - Typed properties, explicit nullability, strict comparisons.
    - Prefer value objects for identifiers where feasible.
- Repositories:
    - Return concrete types (entity, DTO) not arrays unless shape is defined.
    - No SELECT *; use explicit select/joins.
    - Avoid partial objects.
- Transactions:
    - Use EntityManagerInterface->wrapInTransaction() or explicit transaction handling.
    - Do not mix flush logic across layers without clear ownership.

VALIDATION & SECURITY
- Validate all write operations:
    - Use Symfony Validator; never rely on frontend validation.
- Authorization:
    - Use voters or API Platform operation security where appropriate.
    - Never trust client-provided user identifiers; derive from the authenticated token.
- Input handling:
    - Avoid accepting raw arrays; use DTOs with constraints.
    - For enums: use PHP enums and validate mapping explicitly.
- Use operation-level security in API Platform:
    - Use `security` for read access checks.
    - Use `securityPostDenormalize` for write checks that depend on the denormalized object.
- Authorization rules must be explicit per operation (least privilege).
- Prefer voters for complex permissions; keep expressions minimal.
- Never rely solely on client-provided roles/claims for object ownership; confirm ownership server-side.

API PLATFORM RESOURCE DESIGN
- Default-deny:
    - Public endpoints must be explicitly marked (no accidental exposure).
- For “/me” style endpoints:
    - Provide a dedicated operation (e.g. GET /me) that returns the authenticated user's DTO.
    - Never allow GET /users/{id} unless required and authorized.

INPUT/OUTPUT DTO RULES
- Use DTO input for any write where the entity has protected fields.
- Disallow setting ownership fields via input (owner, createdBy, user, tenant).
- On create: server sets owner/tenant based on authenticated user context.

ERROR HANDLING
- Throw specific domain/application exceptions.
- Map exceptions to proper HTTP responses (Problem Details / API Platform error format) without leaking secrets.
- Do not log JWT tokens or Authorization headers.
- Errors must not reveal security configuration or internal user identifiers.

OUTPUT FORMAT
- When generating code, include:
    - Full class with namespace and use statements.
    - Strict types declaration where relevant.
    - PHPDoc only for generics/array shapes or non-obvious contracts.
- Do not include explanations unless explicitly requested.


AUTHENTICATION (JWT)
- Authentication is via Authorization: Bearer <JWT>.
- Never accept user identity from request body/query (e.g. userId, accountId). Always derive the current user from the security token.
- Do not store JWTs in localStorage. If a frontend must store a token, prefer in-memory; for browser persistence prefer httpOnly secure cookies via a BFF/proxy (if applicable).
- All endpoints that read or modify private data must require a valid JWT unless explicitly public.

TESTING
- For any endpoint changes, include an example of a functional test:
    - authenticated request with JWT
    - unauthenticated request returns 401
    - forbidden request returns 403