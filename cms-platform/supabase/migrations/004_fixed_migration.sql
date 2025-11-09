-- Fixed Complete Database Setup
-- Creates tables and adds columns in correct order

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DO $$ BEGIN
    CREATE TYPE post_status AS ENUM ('draft', 'review', 'published');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- =============================================
-- STEP 1: CREATE OR UPDATE TENANTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS tenants (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    wp_api_url TEXT NOT NULL,
    wp_token TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add missing columns to tenants FIRST (before creating indexes)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'tenants' AND column_name = 'brand_tone') THEN
        ALTER TABLE tenants ADD COLUMN brand_tone TEXT;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'tenants' AND column_name = 'llm_model') THEN
        ALTER TABLE tenants ADD COLUMN llm_model TEXT DEFAULT 'gpt-4';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'tenants' AND column_name = 'prompt_base') THEN
        ALTER TABLE tenants ADD COLUMN prompt_base TEXT DEFAULT 'Rewrite this content professionally while maintaining the key information and intent.';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'tenants' AND column_name = 'active') THEN
        ALTER TABLE tenants ADD COLUMN active BOOLEAN DEFAULT true;
    END IF;
END $$;

-- Now create indexes (after columns exist)
CREATE INDEX IF NOT EXISTS idx_tenants_slug ON tenants(slug);
CREATE INDEX IF NOT EXISTS idx_tenants_active ON tenants(active);

-- =============================================
-- STEP 2: CATEGORIES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug);

INSERT INTO categories (name, slug, description)
SELECT 'Casino', 'casino', 'Casino reviews and information'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'casino')
UNION ALL
SELECT 'Game', 'game', 'Game reviews and guides'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'game')
UNION ALL
SELECT 'Bonus', 'bonus', 'Bonus offers and promotions'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'bonus')
UNION ALL
SELECT 'Guide', 'guide', 'How-to guides and tutorials'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'guide');

-- =============================================
-- STEP 3: POSTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS posts (
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

CREATE INDEX IF NOT EXISTS idx_posts_tenant_id ON posts(tenant_id);
CREATE INDEX IF NOT EXISTS idx_posts_category_id ON posts(category_id);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug);

-- =============================================
-- STEP 4: PUBLISH LOG TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS publish_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID REFERENCES tenants(id) ON DELETE SET NULL,
    post_id UUID REFERENCES posts(id) ON DELETE SET NULL,
    target_url TEXT NOT NULL,
    response_code INTEGER,
    response_message TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_publish_log_tenant_id ON publish_log(tenant_id);
CREATE INDEX IF NOT EXISTS idx_publish_log_post_id ON publish_log(post_id);
CREATE INDEX IF NOT EXISTS idx_publish_log_created_at ON publish_log(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_publish_log_response_code ON publish_log(response_code);

-- =============================================
-- STEP 5: CONTENT HISTORY TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS content_history (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    parent_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    version_num INTEGER NOT NULL,
    raw_text TEXT,
    rewritten_text TEXT,
    rewrite_model TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_content_history_parent_id ON content_history(parent_id);
CREATE INDEX IF NOT EXISTS idx_content_history_version_num ON content_history(version_num);
CREATE INDEX IF NOT EXISTS idx_content_history_created_at ON content_history(created_at DESC);

-- =============================================
-- TRIGGERS
-- =============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

DROP TRIGGER IF EXISTS update_tenants_updated_at ON tenants;
CREATE TRIGGER update_tenants_updated_at BEFORE UPDATE ON tenants
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_categories_updated_at ON categories;
CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_posts_updated_at ON posts;
CREATE TRIGGER update_posts_updated_at BEFORE UPDATE ON posts
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =============================================
-- VERSIONING
-- =============================================
CREATE OR REPLACE FUNCTION create_content_version()
RETURNS TRIGGER AS $$
DECLARE
    next_version INTEGER;
BEGIN
    IF NEW.rewritten_text IS DISTINCT FROM OLD.rewritten_text THEN
        SELECT COALESCE(MAX(version_num), 0) + 1 INTO next_version
        FROM content_history WHERE parent_id = NEW.id;

        INSERT INTO content_history (parent_id, version_num, raw_text, rewritten_text)
        VALUES (NEW.id, next_version, NEW.raw_text, NEW.rewritten_text);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS auto_version_content ON posts;
CREATE TRIGGER auto_version_content AFTER UPDATE ON posts
    FOR EACH ROW EXECUTE FUNCTION create_content_version();

-- =============================================
-- ROW LEVEL SECURITY
-- =============================================
ALTER TABLE tenants ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;
ALTER TABLE publish_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE content_history ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Users can view their tenants" ON tenants;
DROP POLICY IF EXISTS "Service role has full access to tenants" ON tenants;
DROP POLICY IF EXISTS "Anyone can view categories" ON categories;
DROP POLICY IF EXISTS "Service role has full access to categories" ON categories;
DROP POLICY IF EXISTS "Users can view posts for their tenants" ON posts;
DROP POLICY IF EXISTS "Service role has full access to posts" ON posts;
DROP POLICY IF EXISTS "Users can view publish logs for their tenants" ON publish_log;
DROP POLICY IF EXISTS "Service role has full access to publish_log" ON publish_log;
DROP POLICY IF EXISTS "Users can view content history for their posts" ON content_history;
DROP POLICY IF EXISTS "Service role has full access to content_history" ON content_history;

CREATE POLICY "Users can view their tenants" ON tenants FOR SELECT USING (true);
CREATE POLICY "Service role has full access to tenants" ON tenants USING (auth.role() = 'service_role');

CREATE POLICY "Anyone can view categories" ON categories FOR SELECT USING (true);
CREATE POLICY "Service role has full access to categories" ON categories USING (auth.role() = 'service_role');

CREATE POLICY "Users can view posts for their tenants" ON posts FOR SELECT USING (true);
CREATE POLICY "Service role has full access to posts" ON posts USING (auth.role() = 'service_role');

CREATE POLICY "Users can view publish logs for their tenants" ON publish_log FOR SELECT USING (true);
CREATE POLICY "Service role has full access to publish_log" ON publish_log USING (auth.role() = 'service_role');

CREATE POLICY "Users can view content history for their posts" ON content_history FOR SELECT USING (true);
CREATE POLICY "Service role has full access to content_history" ON content_history USING (auth.role() = 'service_role');
