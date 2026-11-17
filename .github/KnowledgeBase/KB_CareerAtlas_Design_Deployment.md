# CareerAtlas AWS Deployment

Status: AWS is the confirmed deployment platform. This document records the supplied environment and the decisions needed for implementation. Infrastructure, live access, and deployment commands have not been implemented or verified.

## Confirmed decisions

| Item | Decision | Evidence |
| --- | --- | --- |
| Platform | AWS | Product-owner instruction, 2026-11-17 |
| AWS experience | New AWS experience | Supplied workspace guidance |
| Selected Region | `ap-southeast-2` | Supplied local AWS configuration; verify before resource creation |
| CLI profile | `default` | Supplied local AWS configuration; identity not yet verified |
| Application | PHP/Laravel with Vue 3/TypeScript | Product technology baseline |
| Database engine | PostgreSQL | Product technology baseline |
| Model integration | Provider-neutral server adapter | AI provider remains a separate decision |

Use "project" for the AWS project and "team member" for a human collaborator. Use AWS Settings for project management, billing, team members, and spend limits; use the AWS Management Console for resources. An AWS project and a CareerAtlas portfolio project are different concepts.

## Environment constraints

The following requirements come from the supplied new AWS experience guidance. Recheck applicable official documentation before implementation.

- Direct the user to verify the selected Region at **AWS Settings > View all projects > Overview > Additional Info > Region**. If they cannot confirm it there, inspect the relevant non-secret configuration in `~/.aws/config`. Do not infer Region from the user's language or location.
- Create Regional resources only in the assigned selected Region. Do not use cross-Region replication, multi-Region KMS keys, CloudFormation StackSets, or Route 53 geolocation, latency-based, or failover routing.
- Do not use Lambda@Edge. CloudFront global actions may use `us-east-1`; this does not permit creating application Lambda or API Gateway resources there.
- Only the documented WAF and CloudWatch Logs dependencies of global resources may use the `us-east-1` exception. An inventory that includes those resources must inspect both that Region and the selected Region.
- AWS manages human access and organizational controls. Do not modify managed protections or assign roles to team members unnecessarily. Application and deployment services still need appropriately scoped service permissions.
- Plan and service availability must be checked before choosing a service. Current plan status, spend limit, resource inventory, and advanced-feature activation status are unknown.

AWS documents managed project controls and plan-dependent service availability in [Managed policies for your organization](https://docs.aws.amazon.com/accounts/latest/reference/scps-and-rcps-for-projects.html) and [Supported services for the new AWS experience](https://docs.aws.amazon.com/accounts/latest/reference/supported-services-sign-up-new.html).

## Decisions still required

| Area | Decision to record during implementation |
| --- | --- |
| Application hosting | Runtime service, compatible PHP environment, scaling and health checks |
| PostgreSQL hosting | Hosting service, version, connection handling, persistence and restore procedure |
| Web delivery | Frontend integration, domain, HTTPS, asset delivery and cache rules |
| Identity and secrets | Application login design, service permissions, runtime secret injection |
| Delivery | Infrastructure definition approach, build tooling, release mechanism and rollback |
| Operations | Logging, alerts, retention, backup/recovery objectives and cost estimate |
| AI | Provider, model, availability, limits and data handling |

AWS hosting alone does not select a particular compute service, managed database product, CI/CD service, or model provider. Resolve these choices against the existing stack, selected Region, plan support, expected workload, and operating budget. Preserve PostgreSQL and the existing application boundaries.

## Implementation workflow

1. Read current project instructions. For AWS work, search documentation with topic `agent_skills` and retrieve the matching skill when the AWS MCP tools are available. Prefer AWS MCP tools for AWS operations.
2. Before building, establish the user's help level: "How much guidance would you like from me? Low (I only flag security risks), medium (I ask a couple of clarifying questions if something seems off), or high (I explain what I'm doing, suggest alternatives, and flag best practices)." No help level is recorded yet.
3. Verify the intended AWS project, selected Region, and whether it is on the Free or Paid Plan. The guidance identifies `aws freetier get-account-plan-state` for plan inspection; execute through the available AWS tool, and record the result without credentials. Consult the supported-service list for that plan.
4. Define a concrete service design and cost estimate, then record the selected services and deployment procedure. Keep local development and tests reproducible.
5. Build and test an immutable application artifact. Keep credentials out of source, build output, and logs. If an AWS CI/CD pipeline is selected, follow its current service-specific guidance for secret references and service permissions.
6. Provision and release within the authorized scope. Coordinate schema migrations, persistent data, health checks, and recovery; application rollback must not assume every data migration can be reversed safely.
7. Verify the deployed P0 user flow, access isolation, publication/revocation behavior, citations, and the manual fallback during AI failure. Record actual commands, environment, artifact revision, and outcomes.
8. After resources are successfully created, ask whether to retain them or clean them up to manage cost. Record the choice and execute only the authorized action.

## Operational checks

For sudden Access Denied errors on previously working operations, ask whether a spend limit is configured and direct the user to **AWS Settings > Billing**. Only project owners can change a spend limit. Distinguish this check from an actual diagnosis; inspect permissions and service errors as appropriate.

Use AWS Settings for spend limits and invoices. Use AWS Billing and Cost Management for budgets and cost analysis. For an unavailable service, inspect plan status, the corresponding service list, and unsupported capabilities before proposing any change to advanced features.

Publication revocation must still invalidate server access, retrieval, and future agent responses after deployment. Any cache or delivery layer must preserve those semantics. Backups and persisted records must survive an application release according to the chosen retention policy.

## Verification status

Only documentation validation has been performed. No AWS project inspection, plan lookup, resource creation, deployment, pricing estimate, or live smoke test has been performed for this change. Concrete operational commands belong in the runbook after the service design is implemented and tested.

Related: [Architecture](KB_CareerAtlas_Design_Architecture.md), [Building](../Guidelines/Building.md), [Testing](../Guidelines/Testing.md).
