# EU Data Residency & GDPR Compliance

## Overview

Timeclocker is designed from the ground up to meet the strict privacy and data protection requirements of the European Union. This document outlines our approach to data residency, GDPR compliance, and how we protect your data.

## Data Residency

### EU-Only Data Centers

All Timeclocker data is stored exclusively in data centers located within the European Union:

- **Primary Region**: Germany (Frankfurt)
- **Backup Region**: Netherlands (Amsterdam)
- **Database**: PostgreSQL hosted on EU infrastructure
- **File Storage**: AWS S3 EU regions (eu-central-1, eu-west-1)
- **Backups**: Encrypted backups stored in EU regions only

### No Cross-Border Data Transfers

- **Zero US Data Transfer**: Your data never leaves the European Economic Area (EEA)
- **No Third-Party US Services**: We avoid US-based analytics, tracking, or monitoring services
- **EU Service Providers Only**: All infrastructure providers are EU-based or have EU data processing agreements

## GDPR Compliance

### Legal Basis

Timeclocker processes personal data under the following legal bases:

1. **Contract** - To provide time tracking and invoicing services
2. **Consent** - For optional features like newsletters or marketing
3. **Legitimate Interest** - For service improvement and security
4. **Legal Obligation** - For tax and accounting records (when required)

### Data Protection Rights

As a Timeclocker user, you have the following rights under GDPR:

#### Right to Access (Article 15)
- View all personal data we hold about you
- Access via: Settings → Privacy Dashboard → Download Data

#### Right to Rectification (Article 16)
- Correct inaccurate or incomplete data
- Update via: Profile Settings

#### Right to Erasure (Article 17)
- Request complete deletion of your account and data
- Access via: Settings → Privacy Dashboard → Delete Account
- **Processing Time**: Completed within 48 hours

#### Right to Data Portability (Article 20)
- Export all your data in machine-readable formats (CSV, JSON)
- Includes: time entries, projects, clients, invoices, reports
- Access via: Settings → Privacy Dashboard → Export Data

#### Right to Restrict Processing (Article 18)
- Temporarily restrict processing while verifying accuracy
- Contact: privacy@timeclocker.app

#### Right to Object (Article 21)
- Object to processing based on legitimate interest
- Opt-out of non-essential processing via: Settings

#### Right to Withdraw Consent (Article 7)
- Withdraw consent for newsletter, marketing, or optional features
- Access via: Settings → Communications

### Data Processing Agreement (DPA)

For organization accounts requiring a Data Processing Agreement:

- **Request DPA**: Email support@timeclocker.app
- **Standard Clauses**: We use EU Standard Contractual Clauses
- **Sub-processors**: Full list available in our Privacy Policy

## Data Storage & Security

### Encryption

- **In Transit**: TLS 1.3 for all connections (HTTPS only)
- **At Rest**: AES-256 encryption for all stored data
- **Backups**: Encrypted backups with separate key management
- **Passwords**: Bcrypt hashing with unique salts

### Access Controls

- **2FA Available**: Two-factor authentication for all users
- **Role-Based Access**: Owner, Admin, Editor, Viewer roles
- **Session Management**: Secure session tokens, configurable timeout
- **API Security**: OAuth 2.0 with scoped access tokens

### Data Retention

| Data Type | Retention Period | After Deletion |
|-----------|------------------|----------------|
| Active Account Data | Duration of account | 48h to complete removal |
| Deleted Account Data | 30 days (recovery period) | Permanently erased |
| Audit Logs | 90 days | Automatically purged |
| Backups | 30 days rolling | Old backups auto-deleted |
| Financial Records* | 7 years (if required by law) | Anonymized after legal period |

*Only for paid customers where legally required for tax compliance

### Sub-processors

Timeclocker uses the following EU-based sub-processors:

| Service | Purpose | Location | DPA Available |
|---------|---------|----------|---------------|
| AWS Europe | Database & Storage | Germany, Netherlands | Yes |
| Hetzner | Application Hosting | Germany | Yes |
| Gotenberg | PDF Generation | Self-hosted (EU) | N/A |
| Email Provider* | Transactional Emails | Germany | Yes |

*Configurable - you can use your own SMTP server

## Privacy-First Features

### Privacy Dashboard

Every user has access to a comprehensive Privacy Dashboard:

- **View All Data**: See exactly what data we store about you
- **Export Data**: One-click export in multiple formats
- **Delete Account**: Immediate account deletion with 30-day recovery period
- **Download Audit Log**: See all changes to your account
- **Manage Consent**: Control optional data processing

### Minimal Data Collection

We collect only what's necessary:

- **No Analytics**: We don't use Google Analytics or similar trackers
- **No Ad Tracking**: No advertising pixels or retargeting
- **No Social Widgets**: No embedded social media trackers
- **Essential Cookies Only**: Only cookies required for functionality

### Transparent Logging

- **Audit Trail**: Every change is logged (who, what, when)
- **User Access**: You can view your own audit log
- **Retention**: Audit logs kept for 90 days, then purged
- **Export**: Include audit log in data export

## Compliance Certifications

### Current Status

- ✅ **GDPR Compliant**: Full compliance with EU GDPR
- ✅ **Open Source**: Code publicly auditable (AGPL-3.0)
- ⏳ **ISO 27001**: Planned for Q3 2025
- ⏳ **SOC 2 Type II**: Planned for Q4 2025

### Regular Audits

- **Security Audits**: Quarterly penetration testing
- **Code Audits**: Continuous static analysis (PHPStan Level 7)
- **Dependency Scanning**: Automated vulnerability scanning
- **GDPR Reviews**: Annual compliance review by legal counsel

## For Self-Hosters

If you're self-hosting Timeclocker, follow these recommendations for EU compliance:

### Infrastructure Checklist

- [ ] Use EU-based hosting providers (AWS eu-central-1, Hetzner Germany, etc.)
- [ ] Enable encryption at rest for databases
- [ ] Configure TLS 1.3 for all connections
- [ ] Set up automated backups in EU regions
- [ ] Implement access logging and monitoring
- [ ] Configure SMTP with EU-based email provider
- [ ] Review and sign DPAs with sub-processors
- [ ] Document data processing activities (GDPR Article 30)

### Configuration

Update your `.env` file with EU-specific settings:

```env
# Application
APP_URL=https://timeclocker.yourdomain.eu
APP_FORCE_HTTPS=true

# Database (EU region)
DB_HOST=your-eu-database.eu-central-1.rds.amazonaws.com

# Storage (EU region)
S3_REGION=eu-central-1
S3_ENDPOINT=https://s3.eu-central-1.amazonaws.com

# Email (EU provider)
MAIL_HOST=smtp.eu-mailprovider.com
MAIL_FROM_ADDRESS=noreply@yourdomain.eu
```

### Data Processing Records

Maintain records of processing activities:

- **Purpose of Processing**: Time tracking, invoicing, team management
- **Categories of Data**: User accounts, time entries, projects, invoices
- **Categories of Recipients**: Organization admins, team members
- **Retention Periods**: As defined in this document
- **Security Measures**: Encryption, access controls, backups

## Contact & DPO

For privacy-related questions or to exercise your GDPR rights:

- **Email**: privacy@timeclocker.app
- **Data Protection Officer**: dpo@timeclocker.app
- **Response Time**: Within 72 hours (GDPR Article 12)
- **Supervisory Authority**: You have the right to lodge a complaint with your local data protection authority

## Updates to This Document

- **Last Updated**: January 2025
- **Version**: 1.0
- **Change Log**: https://github.com/solidtime-io/solidtime/blob/main/docs/EU_DATA_RESIDENCY.md
- **Notification**: Users will be notified of material changes via email

## Additional Resources

- [Full Privacy Policy](https://timeclocker.app/privacy)
- [Terms of Service](https://timeclocker.app/terms)
- [Security Policy](../SECURITY.md)
- [GDPR Official Text](https://gdpr-info.eu/)
- [EU Commission: Data Protection](https://ec.europa.eu/info/law/law-topic/data-protection_en)

---

**Note**: This document is specific to Timeclocker. The upstream project (solidtime) may have different data residency policies. Always refer to the documentation of the version you're using.
