# JWT Configuration Notes (Gesdinet_JWT_Refresh_Token)

This document provides explanations for the settings in `gesdinet_jwt_refresh_token.yaml`.

## `refresh_token_class`

-   **Value**: `App\Entity\RefreshToken`
-   **Purpose**: Specifies the Doctrine entity class used to store and manage refresh tokens in the database. This entity should extend `Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken`.

## `ttl` (Time To Live)

-   **Value**: `1296000` (seconds)
-   **Purpose**: Defines the lifespan of a refresh token. After this period, the refresh token becomes invalid and cannot be used to obtain new access tokens.
-   **Current Setting**: 1,296,000 seconds corresponds to 15 days. This value balances user convenience with security.

## `single_use`

-   **Value**: `true`
-   **Purpose**: Enforces that each refresh token can only be used once. This is a security enhancement known as "refresh token rotation."
-   **Mechanism**:
    1.  When a client uses a refresh token (Token A) to get a new access token:
    2.  The server validates Token A.
    3.  The server issues a new access token.
    4.  **Token A is immediately invalidated.**
    5.  The server issues a **new refresh token (Token B)**.
    6.  Both the new access token and Token B are sent to the client.
    7.  The client must then store and use Token B for future refresh operations.
-   **Benefit**: If a refresh token is compromised, its utility is significantly limited. If it has already been used, it's invalid. If used by an attacker before the legitimate user, the legitimate user's subsequent attempt will fail, potentially signaling a compromise.

## `cookie`

Settings for handling refresh tokens via HTTP cookies, which is recommended for browser-based clients to mitigate XSS risks associated with storing tokens in `localStorage`.

-   **`enabled`**: `true`
    -   **Purpose**: Activates cookie-based handling for refresh tokens. The refresh token will be sent to the client as an HTTP cookie.

-   **`samesite`**: `'Strict'`
    -   **Purpose**: Controls whether the cookie is sent with cross-site Browse contexts.
        -   `'Strict'`: The cookie will only be sent for same-site requests (i.e., requests originating from your own site). This is the most secure option for refresh tokens that are only used by your frontend on the same domain.
        -   `'Lax'` is another option, slightly less restrictive.
    -   **Recommendation**: `Strict` is generally preferred for refresh token cookies.

-   **`path`**: `/api/token/refresh`
    -   **Purpose**: Specifies the path for which the cookie is valid. Restricting the path means the browser will only send the cookie when requesting this specific path.
    -   **Value**: Matches the firewall pattern for the token refresh endpoint, ensuring the cookie is only sent where it's needed.

-   **`domain`**: `null`
    -   **Purpose**: Specifies the domain for which the cookie is valid. `null` defaults to the current domain where the cookie is set, which is usually the desired behavior.

-   **`http_only`**: `true`
    -   **Purpose**: **Crucial security setting.** If true, the cookie cannot be accessed via client-side JavaScript (`document.cookie`). This protects the refresh token from being stolen through XSS (Cross-Site Scripting) attacks.

-   **`secure`**: `true`
    -   **Purpose**: **Crucial security setting for production.** If true, the cookie will only be sent from the client to the server over HTTPS connections. This prevents the cookie from being intercepted in transit over unencrypted HTTP.
    -   **Note**: Requires your application to be served over HTTPS in production.

-   **`remove_token_from_body`**: `true`
    -   **Purpose**: When `cookie.enabled` is true, this setting determines if the refresh token should also be removed from the JSON response body.
    -   **Recommendation**: Setting this to `true` is good practice to avoid exposing the token in multiple places when it's already being handled securely via a cookie.

## `token_parameter_name` (Optional)

-   **Default**: `'refresh_token'` (if not specified in the YAML)
-   **Purpose**: If you are *not* using cookies exclusively (e.g., for non-browser clients or if `remove_token_from_body` is false), this defines the name of the parameter in the JSON request body that the bundle expects for the refresh token.
-   **Note**: This is commented out in the current YAML, meaning the default `'refresh_token'` is used if the token is expected in the body.

---

# JWT Configuration Notes (lexik_jwt_authentication.yaml)

## LexikJWTAuthenticationBundle (`lexik_jwt_authentication.yaml`)

This file configures the generation and validation of JSON Web Tokens (JWTs), primarily access tokens.

### Key Signature Settings

-   **`secret_key`**: `'%env(resolve:JWT_SECRET_KEY)%'`
    -   **Purpose**: Path to the private key file (e.g., `config/jwt/private.pem`) or the key content itself. Used for signing tokens with RSA (e.g., RS256, RS512) or EC (e.g., ES256, ES512) algorithms.
    -   **Note**: If using HMAC algorithms (HS256, HS384, HS512), this would be the actual secret string. However, asymmetric keys (RSA/EC) are generally recommended. The `%env(resolve:...)%` syntax allows Symfony to resolve the path from the environment variable.

-   **`public_key`**: `'%env(resolve:JWT_PUBLIC_KEY)%'`
    -   **Purpose**: Path to the public key file (e.g., `config/jwt/public.pem`) or the key content. Used for verifying tokens signed with RSA or EC algorithms.
    -   **Note**: Not used with HMAC algorithms.

-   **`pass_phrase`**: `'%env(JWT_PASSPHRASE)%'`
    -   **Purpose**: The passphrase used to encrypt your private key. It's crucial to protect this passphrase.
    -   **Security**: Store this in your `.env.local` file and ensure it's not committed to version control if it's a sensitive value.

**Key Generation**: These keys should be generated using the command: `php bin/console lexik:jwt:generate-keypair`

### `token_ttl` (Access Token Time To Live)

-   **Value**: `900` (seconds)
-   **Purpose**: Defines the lifespan of an **access token**. After this period, the access token becomes invalid and the client must use a refresh token to obtain a new one, or re-authenticate.
-   **Current Setting**: 900 seconds corresponds to 15 minutes. This short lifespan enhances security by limiting the time an attacker can use a compromised access token.

### Other Common Settings (Defaults or Specific Choices)

-   **`authorization_header`** (Commented out - uses defaults)
    -   **Default Behavior**: The bundle expects the JWT in the `Authorization` HTTP header, prefixed with `Bearer `. This is a standard practice.
    -   **Configuration**:
        ```yaml
        # authorization_header:
        #     enabled: true
        #     prefix:  Bearer
        #     name:    Authorization
        ```

-   **`throw_exceptions`** (Commented out - relies on default authenticator behavior)
    -   **Default Behavior**: Authentication failures (invalid token, expired token) will throw exceptions. Symfony's exception handling mechanism (often customized by API Platform) will then convert these into appropriate JSON error responses (e.g., HTTP 401). This is the desired behavior for APIs.

-   **`set_cookies`** (Commented out - not used for access tokens in this setup)
    -   **Reasoning**:
        -   **Access Tokens**: Typically returned in the JSON response body after a successful login. The client-side application (e.g., SPA) stores it (e.g., in JavaScript memory) and sends it in the `Authorization` header for subsequent API requests. Using cookies for access tokens can be complex (e.g., `HttpOnly` makes it inaccessible to JS for inclusion in headers).
        -   **Refresh Tokens**: Handled by `GesdinetJWTRefreshTokenBundle` using secure, `HttpOnly` cookies as configured in `gesdinet_jwt_refresh_token.yaml`. It's best to avoid configuring refresh token cookies in both bundles to prevent conflicts.
    -   **Configuration Example (if ever needed, but currently not recommended for this project's setup)**:
        ```yaml
        # set_cookies:
        #     access_token:
        #         lifetime: null # Same as token_ttl by default. Use 0 for session cookie.
        #         samesite: strict # or 'lax'
        #         path: /
        #         # domain: null
        #         # httpOnly: true # If true, JS can't read it to send in Authorization header
        #         # secure: true   # Recommended for production
        #     # refresh_token: # Configuration for refresh token cookie IF handled by LexikJWT (not our case)
        ```

---
This provides a comprehensive explanation for the chosen `lexik_jwt_authentication.yaml` configuration.

