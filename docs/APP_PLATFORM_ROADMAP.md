# 5N2 App Platform Roadmap (Branch `app`)

## Objective
Run five major product functions under one platform with shared auth, permissions, notifications, logging, and compliance controls.

## Modules
1. Leads Engine
2. CRM and Email Automation
3. Social and Forum Management
4. WebOps Security and Monitoring
5. SEO Suite and Browser Extension

## Execution Sequence
1. Core platform foundation and guardrails
2. CRM and email connector layer
3. Social/forum connector layer with capability flags
4. WebOps monitors and incident pipeline
5. SEO suite baseline and extension sync API

## Defaults
- Mode: `single_tenant` (5N2 internal)
- Notifications: web + email enabled, sound optional
- Human fallback support: configurable support email destination
- Integrations: API-first, terms-compliant connectors only

## Non-Negotiables
- Keep audit logs for critical actions
- Do not enable high-risk automation without explicit permissions
- Use official APIs where available
- Respect platform access tiers and rate limits
