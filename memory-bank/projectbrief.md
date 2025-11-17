# Project Brief: Squash Court Stats

## Overview

A comprehensive Laravel 12 dashboard application that displays global squash venue and court statistics, replacing a Zoho Analytics solution. The system includes:

1. **Laravel Dashboard Application** - Interactive web dashboard with maps, charts, and statistics
2. **WordPress Plugin** - Allows embedding dashboards and reports into WordPress sites
3. **AI-Powered Categorization System** - Automatically categorizes venues using Google Places API and OpenAI
4. **Court Count Discovery** - AI-powered system to find and verify court counts for venues

## Core Goals

- Replace Zoho Analytics with a modern, self-hosted solution
- Provide interactive visualizations of 6,600+ squash venues worldwide
- Enable WordPress site owners to embed statistics via shortcodes
- Automatically categorize and enrich venue data using AI
- Maintain data accuracy through automated validation and updates

## Key Requirements

### Functional Requirements
- Display global statistics (countries, venues, courts)
- Interactive map with venue locations and clustering
- Multiple dashboard views (world, country, venue types)
- Individual chart selection and custom combinations
- Trivia/reports section with specialized statistics
- WordPress plugin for easy embedding
- Automated venue categorization
- Automated court count discovery

### Technical Requirements
- Laravel 12 with PHP 8.3+
- Remote database connection (MariaDB on atlas.itomic.com)
- Real-time data aggregation with 3-hour caching
- RESTful API endpoints
- Responsive design (Bootstrap 5)
- iframe-based WordPress integration
- Automated deployment via GitHub webhooks

### Non-Functional Requirements
- Fast API responses (< 100ms)
- Mobile-responsive design
- Complete isolation between WordPress and Laravel (no conflicts)
- Comprehensive audit logging for data changes
- Cost-effective AI usage (prioritize free/low-cost APIs)

## Success Criteria

- ✅ All Zoho Analytics functionality replicated
- ✅ Modern, clean UI (not copying Zoho's design)
- ✅ WordPress plugin working with auto-updates
- ✅ Automated categorization reducing manual work
- ✅ Rock-solid deployment workflow
- ✅ Comprehensive documentation for users and developers

