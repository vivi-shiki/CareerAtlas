# Building

## Current runnable check

The repository currently provides documentation only. From PowerShell, run:

```powershell
& 'E:\work\CareerAtlas\.github\Scripts\Validate-Documentation.ps1'
```

The script derives the repository root from its own location. For another checkout, use that checkout's absolute script path. It reads documentation and exits nonzero on validation errors; it does not install dependencies or modify the repository.

## Application bootstrap procedure

When application implementation is requested:

1. Inspect available runtimes and the current repository before choosing compatible versions.
2. Check official Laravel, Vue, TypeScript, and PostgreSQL documentation for version compatibility.
3. Resolve and record the frontend integration style, package manager, supported runtimes, and local database setup.
4. Scaffold only the authorized slice and commit dependency lockfiles when commits are requested.
5. Supply a safe environment-variable example without credentials and document required services.
6. Run the actual install, build, and smoke checks, then record exact successful commands and prerequisites here.

No Composer, frontend, migration, container, cloud, or application test command is verified yet. Do not present a conventional command as runnable until the required manifests and scripts exist.

## AWS deployment preparation

AWS is the selected platform. Follow [Deployment](../KnowledgeBase/KB_CareerAtlas_Design_Deployment.md) for supplied configuration, Region restrictions, plan checks, service-selection decisions, and the release workflow.

During implementation, document the actual artifact build, environment configuration, migrations, release, health verification, and recovery commands here or in a linked runbook. Use the configured selected Region `ap-southeast-2` after verification; do not substitute another Region from a generic example. Keep local build/test commands separate from commands that create or modify AWS resources.
