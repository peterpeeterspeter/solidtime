-- Multi-Tenant Content Delivery Platform - Initial Schema
-- Version: 1.0
-- Description: Core database schema for multi-tenant CMS platform

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create enum for post status
CREATE TYPE post_status AS ENUM ('draft', 'review', 'published');

-- =============================================
-- TENANTS TABLE
-- =============================================
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    wp_api_url TEXT NOT NULL,
    wp_token TEXT NOT NULL,
    brand_tone TEXT,
    llm_model TEXT DEFAULT 'gpt-4',
    prompt_base TEXT DEFAULT 'Rewrite this content professionally while maintaining the key information and intent.',
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add index on slug for faster lookups
CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_active ON tenants(active);

-- =============================================
-- CATEGORIES TABLE
-- =============================================
CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add index on slug
CREATE INDEX idx_categories_slug ON categories(slug);

-- =============================================
-- POSTS TABLE
-- =============================================
CREATE TABLE posts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    category_id UUID REFERENCES categories(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    raw_text TEXT,
    rewritten_text TEXT,
    affiliate_link TEXT,
    seo_title TEXT,
    seo_description TEXT,
    status post_status DEFAULT 'draft',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(tenant_id, slug)
);

-- Add indexes for common queries
CREATE INDEX idx_posts_tenant_id ON posts(tenant_id);
CREATE INDEX idx_posts_category_id ON posts(category_id);
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_slug ON posts(slug);

-- =============================================
-- PUBLISH LOG TABLE
-- =============================================
CREATE TABLE publish_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID REFERENCES tenants(id) ON DELETE SET NULL,
    post_id UUID REFERENCES posts(id) ON DELETE SET NULL,
    target_url TEXT NOT NULL,
    response_code INTEGER,
    response_message TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add indexes for log queries
CREATE INDEX idx_publish_log_tenant_id ON publish_log(tenant_id);
CREATE INDEX idx_publish_log_post_id ON publish_log(post_id);
CREATE INDEX idx_publish_log_created_at ON publish_log(created_at DESC);
CREATE INDEX idx_publish_log_response_code ON publish_log(response_code);

-- =============================================
-- CONTENT HISTORY TABLE
-- =============================================
CREATE TABLE content_history (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    parent_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    version_num INTEGER NOT NULL,
    raw_text TEXT,
    rewritten_text TEXT,
    rewrite_model TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add indexes for version queries
CREATE INDEX idx_content_history_parent_id ON content_history(parent_id);
CREATE INDEX idx_content_history_version_num ON content_history(version_num);
CREATE INDEX idx_content_history_created_at ON content_history(created_at DESC);

-- =============================================
-- TRIGGERS FOR UPDATED_AT
-- =============================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply trigger to tables
CREATE TRIGGER update_tenants_updated_at BEFORE UPDATE ON tenants
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_posts_updated_at BEFORE UPDATE ON posts
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =============================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- =============================================

-- Enable RLS on all tables
ALTER TABLE tenants ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;
ALTER TABLE publish_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE content_history ENABLE ROW LEVEL SECURITY;

-- Tenants policies (example - adjust based on your auth setup)
CREATE POLICY "Users can view their tenants" ON tenants
    FOR SELECT USING (true); -- Adjust based on user-tenant relationship

CREATE POLICY "Service role has full access to tenants" ON tenants
    USING (auth.role() = 'service_role');

-- Categories policies
CREATE POLICY "Anyone can view categories" ON categories
    FOR SELECT USING (true);

CREATE POLICY "Service role has full access to categories" ON categories
    USING (auth.role() = 'service_role');

-- Posts policies
CREATE POLICY "Users can view posts for their tenants" ON posts
    FOR SELECT USING (true); -- Adjust based on user-tenant relationship

CREATE POLICY "Service role has full access to posts" ON posts
    USING (auth.role() = 'service_role');

-- Publish log policies
CREATE POLICY "Users can view publish logs for their tenants" ON publish_log
    FOR SELECT USING (true);

CREATE POLICY "Service role has full access to publish_log" ON publish_log
    USING (auth.role() = 'service_role');

-- Content history policies
CREATE POLICY "Users can view content history for their posts" ON content_history
    FOR SELECT USING (true);

CREATE POLICY "Service role has full access to content_history" ON content_history
    USING (auth.role() = 'service_role');

-- =============================================
-- INITIAL DATA (OPTIONAL)
-- =============================================

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES
    ('Casino', 'casino', 'Casino reviews and information'),
    ('Game', 'game', 'Game reviews and guides'),
    ('Bonus', 'bonus', 'Bonus offers and promotions'),
    ('Guide', 'guide', 'How-to guides and tutorials');

-- =============================================
-- FUNCTIONS FOR VERSIONING
-- =============================================

-- Function to automatically create content history on rewrite
CREATE OR REPLACE FUNCTION create_content_version()
RETURNS TRIGGER AS $$
DECLARE
    next_version INTEGER;
BEGIN
    -- Only create version if rewritten_text changed
    IF NEW.rewritten_text IS DISTINCT FROM OLD.rewritten_text THEN
        -- Get next version number
        SELECT COALESCE(MAX(version_num), 0) + 1 INTO next_version
        FROM content_history
        WHERE parent_id = NEW.id;

        -- Insert new version
        INSERT INTO content_history (parent_id, version_num, raw_text, rewritten_text)
        VALUES (NEW.id, next_version, NEW.raw_text, NEW.rewritten_text);
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply versioning trigger
CREATE TRIGGER auto_version_content AFTER UPDATE ON posts
    FOR EACH ROW EXECUTE FUNCTION create_content_version();
