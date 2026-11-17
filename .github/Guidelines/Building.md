# Building

## Current runnable check

The repository currently provides documentation only. From PowerShell, run:

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

Run from the repository root. The script derives that root from its own location and can also be invoked by its path from another working directory. It reads documentation and exits nonzero on validation errors; it does not install dependencies or modify the repository.

## Application bootstrap procedure

When application implementation is requested:

1. Inspect available runtimes and the current repository before choosing compatible versions.
2. Check official Laravel, Vue, TypeScript, and PostgreSQL documentation for version compatibility.
3. Resolve and record the frontend integration style, package manager, supported runtimes, and local database setup.
4. Scaffold only the authorized slice and commit dependency lockfiles when commits are requested.
5. Supply a safe environment-variable example without credentials and document required services.
6. Run the actual install, build, and smoke checks, then record exact successful commands and prerequisites here.

No Composer, frontend, migration, container, cloud, or application test command is verified yet. Do not present a conventional command as runnable until the required manifests and scripts exist.

## Deployment preparation

Follow the shared [Deployment contract](../KnowledgeBase/KB_CareerAtlas_Design_Deployment.md). Record portable artifact builds, migration behavior, health checks, and recovery procedures here as they are implemented.

Before operating a concrete environment, read its ignored `.local/docs/` instructions. Keep actual service commands, resource names, credential references, plan checks, and environment outcomes there. Public examples must use placeholders and remain usable without the local companion.
