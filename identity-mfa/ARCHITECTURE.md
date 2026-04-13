# Identity + MFA System Architecture

## System Overview

The Identity + MFA system is designed as a microservice-oriented architecture that provides secure authentication and authorization services. The system follows the principles of security-first design, scalability, and maintainability.

## High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Vue.js Admin  │    │   Mobile App    │    │   Web Client    │
│     Panel       │    │                 │    │                 │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          └──────────────────────┼──────────────────────┘
                                 │
                    ┌─────────────▼─────────────┐
                    │      API Gateway          │
                    │   (Rate Limiting, CORS)   │
                    └─────────────┬─────────────┘
                                 │
                    ┌─────────────▼─────────────┐
                    │    Symfony Backend        │
                    │  (Authentication Logic)   │
                    └─────────────┬─────────────┘
                                 │
          ┌──────────────────────┼──────────────────────┐
          │                      │                      │
┌─────────▼───────┐    ┌─────────▼───────┐    ┌─────────▼───────┐
│   PostgreSQL    │    │     Redis       │    │   Audit Logs    │
│   (User Data)   │    │   (Sessions)    │    │   (Security)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Component Details

### 1. Frontend Layer

#### Vue.js Admin Panel
- **Framework**: Vue.js 3 with Composition API
- **Language**: TypeScript for type safety
- **UI Library**: Vuetify 3 for consistent design
- **State Management**: Pinia for reactive state
- **HTTP Client**: Axios with interceptors
- **Build Tool**: Vite for fast development

**Key Features**:
- Real-time user management
- Security event monitoring
- MFA policy configuration
- Audit log visualization
- Role and permission management

### 2. API Gateway Layer

#### Nginx Reverse Proxy
- **SSL Termination**: TLS 1.3 encryption
- **Rate Limiting**: Per-IP and per-user limits
- **CORS Handling**: Configurable cross-origin policies
- **Load Balancing**: Round-robin distribution
- **Health Checks**: Automatic failover

**Configuration**:
```nginx
upstream symfony_backend {
    server symfony:9000;
    server symfony:9001 backup;
}

server {
    listen 443 ssl http2;
    server_name api.identity-mfa.com;
    
    ssl_certificate /etc/ssl/certs/identity-mfa.crt;
    ssl_certificate_key /etc/ssl/private/identity-mfa.key;
    
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        proxy_pass http://symfony_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 3. Backend Layer

#### Symfony Application
- **Framework**: Symfony 6.4 with PHP 8.1+
- **Architecture**: Hexagonal architecture pattern
- **Dependency Injection**: Symfony DI container
- **Event System**: Symfony EventDispatcher
- **Validation**: Symfony Validator component

**Core Components**:

##### Authentication Provider
```php
<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class MfaAuthenticationProvider implements AuthenticationProviderInterface
{
    public function authenticate(TokenInterface $token): TokenInterface
    {
        // Custom authentication logic
        // TOTP validation
        // JWT token generation
    }
    
    public function supports(TokenInterface $token): bool
    {
        return $token instanceof MfaToken;
    }
}
```

##### JWT Token Management
- **Library**: LexikJWTAuthenticationBundle
- **Algorithm**: RS256 (RSA with SHA-256)
- **Token Lifetime**: 15 minutes for access, 7 days for refresh
- **Claims**: User ID, roles, MFA status, issued at, expiration

##### MFA Implementation
- **Library**: scheb/2fa-bundle
- **Methods**: TOTP, Email OTP, Backup codes
- **TOTP Settings**: 30-second window, 6-digit codes
- **Backup Codes**: 10 single-use codes per user

### 4. Data Layer

#### PostgreSQL Database
- **Version**: PostgreSQL 14+
- **Encoding**: UTF-8
- **Extensions**: uuid-ossp, pgcrypto
- **Connection Pooling**: PgBouncer

**Key Tables**:
```sql
-- Users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(255),
    backup_codes JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Audit logs
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Sessions
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    data BYTEA,
    created_at TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP NOT NULL
);
```

#### Redis Cache
- **Version**: Redis 7+
- **Persistence**: RDB + AOF
- **Memory**: 2GB allocated
- **Eviction Policy**: allkeys-lru

**Usage Patterns**:
- Session storage (TTL: 24 hours)
- Rate limiting counters (TTL: 1 hour)
- JWT blacklist (TTL: token lifetime)
- MFA attempt counters (TTL: 5 minutes)

### 5. Security Layer

#### Authentication Flow
1. **User Login**: Email/password validation
2. **JWT Generation**: Access and refresh tokens
3. **MFA Check**: Determine if MFA is required
4. **TOTP Validation**: Verify time-based code
5. **Session Creation**: Store in Redis
6. **Audit Logging**: Record all security events

#### Security Measures
- **Password Hashing**: Argon2id with salt
- **CSRF Protection**: Symfony CSRF tokens
- **XSS Prevention**: Content Security Policy
- **SQL Injection**: Prepared statements only
- **Rate Limiting**: Per-IP and per-user limits
- **Input Validation**: Symfony Validator constraints

## Scalability Considerations

### Horizontal Scaling
- **Stateless Design**: No server-side session storage
- **Load Balancing**: Multiple Symfony instances
- **Database Sharding**: User-based partitioning
- **Cache Distribution**: Redis Cluster

### Performance Optimization
- **Database Indexing**: Optimized queries
- **Query Caching**: Doctrine query cache
- **HTTP Caching**: ETags and Last-Modified
- **CDN Integration**: Static asset delivery

### Monitoring & Observability
- **Application Metrics**: Custom Symfony metrics
- **Database Monitoring**: Query performance tracking
- **Cache Metrics**: Hit/miss ratios
- **Security Metrics**: Failed login attempts

## Deployment Architecture

### Development Environment
```yaml
# docker-compose.yml
version: '3.8'
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    volumes: ["./nginx.conf:/etc/nginx/nginx.conf"]
    
  symfony:
    build: ./backend
    volumes: ["./backend:/var/www/html"]
    environment:
      - DATABASE_URL=postgresql://user:pass@postgres:5432/identity_mfa
      - REDIS_URL=redis://redis:6379
      
  postgres:
    image: postgres:14
    environment:
      - POSTGRES_DB=identity_mfa
      - POSTGRES_USER=user
      - POSTGRES_PASSWORD=pass
      
  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
```

### Production Environment
- **Kubernetes**: Container orchestration
- **Helm Charts**: Application deployment
- **Ingress Controller**: NGINX Ingress
- **Service Mesh**: Istio for traffic management
- **Secrets Management**: Kubernetes Secrets

## Security Architecture

### Threat Model
1. **Credential Theft**: Mitigated by MFA
2. **Session Hijacking**: Prevented by secure tokens
3. **Brute Force Attacks**: Rate limiting and account lockout
4. **Man-in-the-Middle**: TLS encryption
5. **SQL Injection**: Parameterized queries
6. **XSS Attacks**: Input sanitization and CSP

### Compliance Requirements
- **SOC2 Type II**: Security controls and monitoring
- **GDPR**: Data protection and user rights
- **HIPAA**: Healthcare data protection
- **PCI DSS**: Payment card data security

## Disaster Recovery

### Backup Strategy
- **Database Backups**: Daily automated backups
- **Configuration Backups**: Version-controlled configs
- **Code Backups**: Git repository with multiple remotes
- **Key Backups**: Encrypted JWT signing keys

### Recovery Procedures
- **RTO (Recovery Time Objective)**: 4 hours
- **RPO (Recovery Point Objective)**: 1 hour
- **Failover Process**: Automated with health checks
- **Data Restoration**: Point-in-time recovery

## Future Enhancements

### Planned Features
- **Biometric Authentication**: Fingerprint and face recognition
- **Hardware Token Support**: FIDO2/WebAuthn
- **Social Login**: OAuth2 with Google, Microsoft, GitHub
- **Advanced Analytics**: User behavior analysis
- **Machine Learning**: Anomaly detection

### Technology Upgrades
- **PHP 8.2**: Performance improvements
- **Symfony 7**: Latest features and security
- **Vue.js 4**: Enhanced performance
- **PostgreSQL 15**: Advanced features

---

This architecture provides a robust, scalable, and secure foundation for enterprise identity management while maintaining high performance and compliance with industry standards.
