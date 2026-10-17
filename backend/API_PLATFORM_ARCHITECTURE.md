# FeedNews backend API architecture

The HTTP API is implemented with API Platform state providers and processors. Doctrine entities are persistence models and are not the public API contract.

## Structure

Each domain follows the same separation:

- `Dto/*Dto.php`: DTO-only API Platform resources that declare URI templates, HTTP methods, input/output DTOs and the provider or processor handling the operation.
- `Dto/Request/`: request contracts and validation rules.
- `Dto/Response/`: response contracts. API responses are not assembled as anonymous controller arrays.
- `Provider/`: read side. Providers may query repositories and map entities to response DTOs.
- `Processor/`: write side. Processors receive validated request DTOs, execute the mutation and return response DTOs.
- `Mapper/`: maps persistence/domain objects to response DTOs where mapping is reused or non-trivial.
- `Repository/`: Doctrine persistence queries. Repositories are not injected into JSON API controllers.
- `Service/`: domain/application behavior that does not belong in HTTP transport code.

## Controllers

JSON endpoints previously implemented by `AdminController`, `AuthController`, `ChecklistController`, `DashboardController`, `FeedController`, `NotesController`, `SettingsController`, `TagsController` and `WeatherController` are now API Platform resources.

`OAuthController` remains a Symfony controller because OAuth authorization and callbacks are browser redirect flows rather than JSON resources. Repository work for OAuth is encapsulated in `OAuthUserService`, so the controller itself does not access persistence.

`PublicController` remains responsible for the SPA/public browser routes.

## Compatibility

The Vue frontend API paths and JSON response property names are intentionally kept compatible with the previous implementation. API Platform is configured to return regular `application/json` rather than JSON-LD so the existing frontend client does not need transport changes.

Login and logout remain Symfony Security firewall endpoints. Registration and session state are API Platform operations.

## Adding an endpoint

For a read endpoint:

1. Define a response DTO.
2. Define the operation on the domain `*Dto` API resource.
3. Implement `ProviderInterface`.
4. Query through a repository/service in the provider.
5. Return the response DTO.

For a write endpoint:

1. Define a request DTO with Symfony Validator constraints.
2. Define a response DTO.
3. Define the operation on the domain `*Dto` API resource using `input`, `output` and `processor`.
4. Implement `ProcessorInterface`.
5. Perform persistence/domain logic in the processor or a dedicated service.
6. Return the response DTO.

Do not add repository access back to controllers.
