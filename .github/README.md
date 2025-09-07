# CI/CD Pipeline Documentation

This directory contains GitHub Actions workflows for the LearnVentures project.

## Workflows Overview

### 1. CI Pipeline (`ci.yml`)
**Triggers:** Push to main/develop branches, Pull Requests
**Purpose:** Comprehensive testing and validation

**Jobs:**
- **Frontend**: React app testing, linting, and building
- **Backend**: Laravel API testing, linting, and validation
- **FastAPI**: Python API testing and validation
- **Integration**: End-to-end testing across all services
- **Security**: Vulnerability scanning
- **Deploy**: Production deployment (main branch only)

### 2. Deploy Pipeline (`deploy.yml`)
**Triggers:** Push to main branch, Manual dispatch
**Purpose:** Production deployment

**Features:**
- Multi-stack deployment (React, Laravel, FastAPI)
- Environment-specific configurations
- Deployment artifact creation
- Support for multiple hosting platforms

### 3. Security Pipeline (`security.yml`)
**Triggers:** Weekly schedule, Push/PR to main/develop
**Purpose:** Security vulnerability scanning

**Features:**
- Trivy vulnerability scanner
- CodeQL analysis
- Dependency auditing (npm, composer, pip)
- SARIF report generation

## Setup Instructions

### 1. Environment Variables
Create the following secrets in your GitHub repository:

**Repository Secrets:**
- `DB_PASSWORD`: Database password for testing
- `JWT_SECRET`: JWT secret key
- `APP_KEY`: Laravel application key

**Optional (for deployment):**
- `AWS_ACCESS_KEY_ID`: AWS access key
- `AWS_SECRET_ACCESS_KEY`: AWS secret key
- `DROPLET_HOST`: DigitalOcean droplet host
- `DROPLET_USERNAME`: DigitalOcean username
- `DROPLET_SSH_KEY`: SSH private key
- `HEROKU_API_KEY`: Heroku API key

### 2. Database Setup
The CI pipeline uses MySQL for testing. The workflow automatically:
- Sets up MySQL 8.0 service
- Creates test database
- Runs migrations and seeders

### 3. Testing Requirements

**Frontend (React):**
- Ensure all tests pass: `npm test`
- ESLint configuration should be in place
- Build should complete successfully: `npm run build`

**Backend (Laravel):**
- PHPUnit tests should be in `tests/` directory
- Laravel Pint for code formatting
- Database migrations and seeders

**FastAPI:**
- Add tests in `tests/` directory
- Install pytest: `pip install pytest pytest-cov`

## Workflow Features

### Caching
- Node.js dependencies cached for faster builds
- PHP Composer dependencies cached
- Python pip dependencies cached

### Parallel Execution
- Frontend, Backend, and FastAPI jobs run in parallel
- Integration tests run after all individual tests pass

### Artifact Management
- Frontend build artifacts stored for 7 days
- Deployment packages stored for 30 days

### Security Scanning
- Weekly vulnerability scans
- Code quality analysis
- Dependency auditing

## Customization

### Adding New Tests
1. **Frontend**: Add tests to `client/src/` and ensure they're discovered by Jest
2. **Backend**: Add tests to `server/tests/` using PHPUnit
3. **FastAPI**: Add tests to `server-FastApi/tests/` using pytest

### Modifying Deployment
Edit `deploy.yml` to add your specific deployment steps:
- Uncomment and configure your hosting platform
- Add custom deployment scripts
- Configure environment-specific settings

### Adding New Checks
1. Create a new workflow file in `.github/workflows/`
2. Define triggers and jobs
3. Add to the main CI pipeline if needed

## Troubleshooting

### Common Issues

**Frontend Build Fails:**
- Check for TypeScript/JavaScript errors
- Ensure all dependencies are in package.json
- Verify environment variables are set

**Backend Tests Fail:**
- Check database connection settings
- Ensure migrations are up to date
- Verify PHP extensions are installed

**FastAPI Tests Fail:**
- Check Python version compatibility
- Ensure all dependencies are in requirements.txt
- Verify test discovery patterns

### Debugging
- Check workflow logs in GitHub Actions tab
- Use `continue-on-error: true` for non-critical steps
- Add debug output with `echo` commands

## Best Practices

1. **Keep workflows fast**: Use caching and parallel execution
2. **Fail fast**: Put critical checks early in the pipeline
3. **Security first**: Run security scans on every PR
4. **Test everything**: Include unit, integration, and e2e tests
5. **Monitor deployments**: Use notifications and status checks

## Support

For issues with the CI/CD pipeline:
1. Check the workflow logs
2. Review this documentation
3. Check GitHub Actions documentation
4. Create an issue in the repository
