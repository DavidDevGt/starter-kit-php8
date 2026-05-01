Feature: Authentication
  As a user
  I want to log in and out securely
  So that only I can access my tenant's data

  Background:
    Given the database has a tenant "Acme Corp" with NIT "123456-7"
    And that tenant has an admin user "admin" with password "Secret123!"

  # ── Login ─────────────────────────────────────────────────────────────────

  Scenario: Successful login
    When I POST to "/login" with:
      | username | admin      |
      | password | Secret123! |
    Then the response status is 200
    And the response JSON contains "success" true
    And the response JSON contains "redirect" "/dashboard"
    And a session cookie is set

  Scenario: Wrong password
    When I POST to "/login" with:
      | username | admin       |
      | password | WrongPass!  |
    Then the response status is 401
    And the response JSON contains "success" false

  Scenario: Missing fields
    When I POST to "/login" with:
      | username | admin |
    Then the response status is 422
    And the response JSON has "errors.password"

  Scenario: Account is inactive
    Given the user "admin" is marked as inactive
    When I POST to "/login" with valid credentials
    Then the response status is 401

  Scenario: Rate limiting after 5 failed attempts
    Given I have failed to log in 5 times from IP "192.168.1.1"
    When I POST to "/login" with wrong credentials from IP "192.168.1.1"
    Then the response status is 429
    And the response header "Retry-After" is present

  Scenario: Already logged-in user hits /login page
    Given I am logged in as "admin"
    When I GET "/login"
    Then I am redirected to "/dashboard"

  # ── Logout ────────────────────────────────────────────────────────────────

  Scenario: Successful logout
    Given I am logged in as "admin"
    When I POST to "/logout" with valid CSRF token
    Then the response redirects to "/login"
    And the session is destroyed
    And session_logs.session_end is populated

  Scenario: Logout without session
    When I POST to "/logout" without a session
    Then the response redirects to "/login"

  # ── Registration ──────────────────────────────────────────────────────────

  Scenario: New tenant registration
    When I POST to "/register" with:
      | company_name | Beta LLC   |
      | nit          | 999888-1   |
      | email        | ceo@beta.com |
      | username     | betaadmin  |
      | password     | Pass1234!  |
    Then the response status is 201
    And a new company row exists with name "Beta LLC"
    And a new user row exists with username "betaadmin"
    And a subscription row exists with status "trial"
    And trial_ends_at is 14 days from now

  Scenario: Duplicate NIT on registration
    Given tenant "Acme Corp" with NIT "123456-7" already exists
    When I POST to "/register" with NIT "123456-7"
    Then the response status is 409
    And the response JSON contains "error"

  Scenario: Weak password on registration
    When I POST to "/register" with password "123"
    Then the response status is 422
    And the response JSON has "errors.password"

  # ── CSRF ──────────────────────────────────────────────────────────────────

  Scenario: POST without CSRF token is rejected
    Given I am logged in as "admin"
    When I POST to "/api/v1/users" without a CSRF token
    Then the response status is 419

  Scenario: POST with valid CSRF token succeeds
    Given I am logged in as "admin"
    And I have a valid CSRF token
    When I POST to "/api/v1/users" with the CSRF token and valid user data
    Then the response status is 201
