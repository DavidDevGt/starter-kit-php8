Feature: Multi-tenancy isolation
  As the system
  I want strict data isolation between tenants
  So that no tenant can ever see another tenant's data

  Background:
    Given tenant "Acme" exists with id 1 and admin user "acme_admin"
    And tenant "Beta" exists with id 2 and admin user "beta_admin"
    And "Acme" has users: Alice (id=10), Bob (id=11)
    And "Beta" has users: Carol (id=20), Dave (id=21)

  # ── Row isolation ─────────────────────────────────────────────────────────

  Scenario: Tenant sees only its own users
    Given I am logged in as "acme_admin" (tenant 1)
    When I GET "/api/v1/users"
    Then the response contains users [Alice, Bob]
    And the response does NOT contain users [Carol, Dave]

  Scenario: Tenant cannot access another tenant's user by ID
    Given I am logged in as "acme_admin" (tenant 1)
    When I GET "/api/v1/users/20"
    Then the response status is 404

  Scenario: Tenant cannot update another tenant's user
    Given I am logged in as "acme_admin" (tenant 1)
    When I PUT "/api/v1/users/21" with any data
    Then the response status is 404

  Scenario: Tenant cannot delete another tenant's user
    Given I am logged in as "acme_admin" (tenant 1)
    When I DELETE "/api/v1/users/21"
    Then the response status is 404

  # ── TenantContext ─────────────────────────────────────────────────────────

  Scenario: TenantContext is set on every authenticated request
    Given I am logged in as "acme_admin" (tenant 1)
    When I make any authenticated API request
    Then TenantContext::id() returns 1

  Scenario: TenantContext is reset after request
    When request A from tenant 1 completes
    And request B from tenant 2 starts
    Then TenantContext::id() returns 2 during request B

  Scenario: Unauthenticated request does not initialize TenantContext
    When I make an unauthenticated request
    Then TenantContext::isSet() returns false

  # ── Subscription gating ───────────────────────────────────────────────────

  Scenario: Active tenant accesses protected resource
    Given "Acme" has an active subscription
    When "acme_admin" calls "/api/v1/users"
    Then the response status is 200

  Scenario: Expired trial tenant is blocked
    Given "Acme" has a trial that ended 1 day ago
    When "acme_admin" calls "/api/v1/users"
    Then the response status is 402
    And the response JSON contains "error" "subscription_required"
    And the response JSON contains "upgrade_url" "/billing/upgrade"

  Scenario: Cancelled subscription blocks access
    Given "Acme" subscription status is "cancelled"
    When "acme_admin" calls "/api/v1/users"
    Then the response status is 402

  # ── User limit per plan ───────────────────────────────────────────────────

  Scenario: Tenant on free plan cannot exceed 1 user
    Given "Acme" is on the "free" plan (max_users = 1)
    And "Acme" already has 1 active user
    When "acme_admin" POSTs to "/api/v1/users" with new user data
    Then the response status is 402
    And the response JSON contains "error" about user limit

  Scenario: Tenant on starter plan can add up to 5 users
    Given "Acme" is on the "starter" plan (max_users = 5)
    And "Acme" has 4 active users
    When "acme_admin" POSTs to "/api/v1/users" with new user data
    Then the response status is 201

  # ── Module access per plan ────────────────────────────────────────────────

  Scenario: Free plan shows only basic modules
    Given "Acme" is on the "free" plan
    When "acme_admin" GETs the navigation modules
    Then the response contains only modules assigned to the "free" plan

  Scenario: Pro plan shows all modules
    Given "Acme" is on the "pro" plan
    When "acme_admin" GETs the navigation modules
    Then the response contains all active modules

  # ── Stripe webhooks ───────────────────────────────────────────────────────

  Scenario: Stripe invoice.paid activates subscription
    Given "Acme" has a "past_due" subscription with stripe_subscription_id "sub_abc"
    When Stripe sends "invoice.paid" for subscription "sub_abc"
    Then "Acme" subscription status becomes "active"
    And current_period_end is updated

  Scenario: Stripe subscription.deleted cancels access
    Given "Acme" has an "active" subscription with stripe_subscription_id "sub_abc"
    When Stripe sends "customer.subscription.deleted" for "sub_abc"
    Then "Acme" subscription status becomes "cancelled"

  Scenario: Webhook with invalid Stripe signature is rejected
    When I POST to "/webhooks/stripe" with a forged signature
    Then the response status is 400
