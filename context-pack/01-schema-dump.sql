--
-- PostgreSQL database dump
--

\restrict lmBIy5kpjpPUevi4bOa140XSJYUo1tRU50pfdXE6pPBZJ7jkty1c5kijO5aaDfI

-- Dumped from database version 16.10 (Homebrew)
-- Dumped by pg_dump version 16.10 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA public;


--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: a_i_credentials; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.a_i_credentials (
    id bigint NOT NULL,
    provider character varying(255) NOT NULL,
    credential_type character varying(255) DEFAULT 'api_key'::character varying NOT NULL,
    encrypted_credentials text NOT NULL,
    metadata json,
    expires_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: a_i_credentials_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.a_i_credentials_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: a_i_credentials_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.a_i_credentials_id_seq OWNED BY public.a_i_credentials.id;


--
-- Name: agent_decisions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_decisions (
    id uuid NOT NULL,
    topic character varying(255) NOT NULL,
    decision text NOT NULL,
    rationale text,
    alternatives json,
    confidence double precision,
    links json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: agent_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_notes (
    id uuid NOT NULL,
    agent_id uuid,
    topic character varying(255) NOT NULL,
    body text NOT NULL,
    kind character varying(255) NOT NULL,
    scope character varying(255) NOT NULL,
    ttl_at timestamp(0) without time zone,
    links json,
    tags json,
    provenance json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: agent_vectors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_vectors (
    id uuid NOT NULL,
    key character varying(255) NOT NULL,
    embedding json NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: article_fragments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.article_fragments (
    article_id bigint NOT NULL,
    fragment_id bigint,
    order_pos integer NOT NULL,
    body text,
    edit_mode character varying(255) DEFAULT 'reference'::character varying NOT NULL,
    CONSTRAINT article_fragments_edit_mode_check CHECK (((edit_mode)::text = ANY (ARRAY[('reference'::character varying)::text, ('copy'::character varying)::text])))
);


--
-- Name: COLUMN article_fragments.fragment_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.article_fragments.fragment_id IS 'NULL when copy-only block';


--
-- Name: COLUMN article_fragments.body; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.article_fragments.body IS 'Snapshot for edit_mode=copy';


--
-- Name: articles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.articles (
    id bigint NOT NULL,
    workspace_id bigint,
    title character varying(255),
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT articles_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('review'::character varying)::text, ('published'::character varying)::text])))
);


--
-- Name: COLUMN articles.meta; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.articles.meta IS 'Article metadata';


--
-- Name: articles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.articles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: articles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.articles_id_seq OWNED BY public.articles.id;


--
-- Name: artifacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.artifacts (
    id uuid NOT NULL,
    owner_id uuid,
    type character varying(255) NOT NULL,
    mime character varying(255),
    path character varying(255) NOT NULL,
    sha256 character varying(64) NOT NULL,
    created_by_tool character varying(255) NOT NULL,
    source_query_id uuid,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bookmarks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bookmarks (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    fragment_ids json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    last_viewed_at timestamp(0) without time zone,
    vault_id bigint,
    project_id bigint
);


--
-- Name: bookmarks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bookmarks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bookmarks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bookmarks_id_seq OWNED BY public.bookmarks.id;


--
-- Name: builds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.builds (
    id bigint NOT NULL,
    article_id bigint NOT NULL,
    workspace_id bigint,
    range_start timestamp(0) without time zone NOT NULL,
    range_end timestamp(0) without time zone NOT NULL,
    kind character varying(32) DEFAULT 'daily'::character varying NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN builds.article_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.builds.article_id IS 'The rendered brief/log';


--
-- Name: COLUMN builds.kind; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.builds.kind IS 'daily, weekly, session';


--
-- Name: builds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.builds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: builds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.builds_id_seq OWNED BY public.builds.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: calendar_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.calendar_events (
    fragment_id bigint NOT NULL,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    location character varying(255),
    attendees json,
    state json
);


--
-- Name: COLUMN calendar_events.attendees; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.calendar_events.attendees IS 'Emails, names';


--
-- Name: COLUMN calendar_events.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.calendar_events.state IS 'RSVP, recurrence, conferencing';


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: chat_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_sessions (
    id bigint NOT NULL,
    title character varying(255),
    summary text,
    messages json,
    metadata json,
    message_count integer DEFAULT 0 NOT NULL,
    last_activity_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    vault_id bigint,
    project_id bigint,
    is_pinned boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    deleted_at timestamp(0) without time zone,
    short_code character varying(255),
    custom_name character varying(255),
    model_provider character varying(255),
    model_name character varying(255)
);


--
-- Name: chat_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_sessions_id_seq OWNED BY public.chat_sessions.id;


--
-- Name: collection_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collection_items (
    collection_id bigint NOT NULL,
    order_pos integer NOT NULL,
    object_type character varying(32) NOT NULL,
    object_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: collections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collections (
    id bigint NOT NULL,
    workspace_id bigint,
    title character varying(255) NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: collections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collections_id_seq OWNED BY public.collections.id;


--
-- Name: command_activity; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.command_activity (
    id uuid NOT NULL,
    slug character varying(255) NOT NULL,
    action character varying(255) NOT NULL,
    run_id uuid,
    user_id bigint,
    payload json,
    ts timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: command_registry; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.command_registry (
    id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    version character varying(255) DEFAULT '1.0.0'::character varying NOT NULL,
    source_path character varying(255) NOT NULL,
    steps_hash character varying(255) NOT NULL,
    capabilities json,
    requires_secrets json,
    reserved boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: command_registry_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.command_registry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: command_registry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.command_registry_id_seq OWNED BY public.command_registry.id;


--
-- Name: command_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.command_runs (
    id uuid NOT NULL,
    slug character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'running'::character varying NOT NULL,
    duration_ms bigint,
    user_id bigint,
    started_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    finished_at timestamp(0) with time zone
);


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    fragment_id bigint NOT NULL,
    full_name character varying(255),
    emails json,
    phones json,
    organization character varying(255),
    state json
);


--
-- Name: COLUMN contacts.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.contacts.state IS 'Rich state data: roles, tags, etc.';


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: file_text; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.file_text (
    fragment_id bigint NOT NULL,
    content text NOT NULL
);


--
-- Name: files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.files (
    fragment_id bigint NOT NULL,
    uri text NOT NULL,
    storage_kind character varying(255) NOT NULL,
    mime character varying(128) NOT NULL,
    bytes bigint,
    content_hash character(64),
    width integer,
    height integer,
    duration_ms integer,
    exif json,
    state json,
    CONSTRAINT files_storage_kind_check CHECK (((storage_kind)::text = ANY (ARRAY[('local'::character varying)::text, ('obsidian'::character varying)::text, ('remote'::character varying)::text, ('s3'::character varying)::text, ('gdrive'::character varying)::text])))
);


--
-- Name: COLUMN files.exif; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.files.exif IS 'Camera, GPS, metadata';


--
-- Name: COLUMN files.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.files.state IS 'Processed flags, privacy, etc.';


--
-- Name: fragment_embeddings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_embeddings (
    id bigint NOT NULL,
    fragment_id bigint NOT NULL,
    provider character varying(255) NOT NULL,
    dims integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    embedding public.vector(1536),
    model character varying(255) NOT NULL,
    content_hash character varying(64) NOT NULL
);


--
-- Name: fragment_embeddings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fragment_embeddings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fragment_embeddings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fragment_embeddings_id_seq OWNED BY public.fragment_embeddings.id;


--
-- Name: fragment_links; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_links (
    id bigint NOT NULL,
    from_id bigint NOT NULL,
    to_id bigint NOT NULL,
    relation character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT fragment_links_relation_check CHECK (((relation)::text = ANY (ARRAY[('similar_to'::character varying)::text, ('refines'::character varying)::text, ('cluster_member'::character varying)::text, ('duplicate_of'::character varying)::text, ('references'::character varying)::text, ('mentions'::character varying)::text, ('child_of'::character varying)::text])))
);


--
-- Name: COLUMN fragment_links.from_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragment_links.from_id IS 'Source fragment ID';


--
-- Name: COLUMN fragment_links.to_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragment_links.to_id IS 'Target fragment ID';


--
-- Name: COLUMN fragment_links.relation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragment_links.relation IS 'Type of relationship between fragments';


--
-- Name: fragment_links_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fragment_links_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fragment_links_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fragment_links_id_seq OWNED BY public.fragment_links.id;


--
-- Name: fragment_metrics_daily; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_metrics_daily (
    day date NOT NULL,
    type character varying(255) NOT NULL,
    created integer DEFAULT 0 NOT NULL,
    updated integer DEFAULT 0 NOT NULL,
    deleted integer DEFAULT 0 NOT NULL
);


--
-- Name: fragment_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_tags (
    fragment_id bigint NOT NULL,
    tag character varying(128) NOT NULL
);


--
-- Name: fragment_type_registry; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_type_registry (
    id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    version character varying(255) DEFAULT '1.0.0'::character varying NOT NULL,
    source_path character varying(255) NOT NULL,
    schema_hash character varying(255) NOT NULL,
    hot_fields json,
    capabilities json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: fragment_type_registry_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fragment_type_registry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fragment_type_registry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fragment_type_registry_id_seq OWNED BY public.fragment_type_registry.id;


--
-- Name: fragments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragments (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    message text NOT NULL,
    tags json,
    relationships json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    category_id bigint,
    source character varying(255),
    state character varying(255),
    metadata json,
    vault character varying(255) DEFAULT 'default'::character varying,
    hash character(64),
    deleted_at timestamp(0) without time zone,
    importance smallint DEFAULT '0'::smallint,
    confidence smallint DEFAULT '0'::smallint,
    pinned boolean DEFAULT false NOT NULL,
    lang character(5),
    workspace_id bigint,
    mime character varying(64),
    object_type_id smallint,
    object_version smallint,
    state_json json,
    source_key character varying(64),
    project_id bigint,
    input_hash character(64),
    hash_bucket integer,
    title character varying(255),
    parsed_entities json,
    selection_stats json,
    type_id bigint,
    edited_message text,
    model_provider character varying(255),
    model_name character varying(255),
    inbox_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    inbox_reason text,
    inbox_at timestamp(0) with time zone,
    reviewed_at timestamp(0) with time zone,
    reviewed_by bigint
);


--
-- Name: COLUMN fragments.hash; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.hash IS 'Content hash for dedupe/idempotency';


--
-- Name: COLUMN fragments.deleted_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.deleted_at IS 'Soft delete with provenance';


--
-- Name: COLUMN fragments.importance; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.importance IS 'Quick ranking for WM/LTM (0-100)';


--
-- Name: COLUMN fragments.confidence; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.confidence IS 'Confidence of auto-tagging/extraction';


--
-- Name: COLUMN fragments.pinned; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.pinned IS 'Guaranteed presence in briefs';


--
-- Name: COLUMN fragments.lang; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.lang IS 'ISO language for tokenization/search';


--
-- Name: COLUMN fragments.workspace_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.workspace_id IS 'Project/workspace facet';


--
-- Name: COLUMN fragments.mime; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.mime IS 'Fast content type filtering';


--
-- Name: COLUMN fragments.object_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.object_type_id IS 'FK to object_types for typed objects';


--
-- Name: COLUMN fragments.object_version; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.object_version IS 'Object type version for migrations';


--
-- Name: COLUMN fragments.state_json; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.state_json IS 'Rich state data as JSON';


--
-- Name: COLUMN fragments.source_key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fragments.source_key IS 'Standardized source identifier';


--
-- Name: fragments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fragments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fragments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fragments_id_seq OWNED BY public.fragments.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: links; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.links (
    fragment_id bigint NOT NULL,
    url text NOT NULL,
    normalized_url text,
    domain character varying(255),
    title character varying(512),
    description text,
    fetched_at timestamp(0) without time zone,
    fetch_status smallint,
    state json
);


--
-- Name: COLUMN links.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.links.state IS 'Rich state data: read/unread, rating, etc.';


--
-- Name: meetings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meetings (
    fragment_id bigint NOT NULL,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    participants json,
    calendar_event_id bigint,
    state json
);


--
-- Name: COLUMN meetings.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.meetings.state IS 'Agenda, minutes, action items';


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: object_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.object_types (
    id smallint NOT NULL,
    key character varying(64) NOT NULL,
    version smallint DEFAULT '1'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN object_types.key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.object_types.key IS 'Object type key: todo, contact, event, etc.';


--
-- Name: COLUMN object_types.version; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.object_types.version IS 'Schema version for migrations';


--
-- Name: object_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.object_types_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: object_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.object_types_id_seq OWNED BY public.object_types.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id bigint NOT NULL,
    vault_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_default boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;


--
-- Name: prompt_registry; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prompt_registry (
    id uuid NOT NULL,
    kind character varying(255) NOT NULL,
    text text NOT NULL,
    variables json,
    version integer DEFAULT 1 NOT NULL,
    tags json,
    owner_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: recall_decisions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.recall_decisions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    query character varying(512) NOT NULL,
    parsed_query json,
    total_results integer NOT NULL,
    selected_fragment_id bigint,
    selected_index integer,
    action character varying(32) DEFAULT 'select'::character varying NOT NULL,
    context json,
    decided_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: recall_decisions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.recall_decisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: recall_decisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.recall_decisions_id_seq OWNED BY public.recall_decisions.id;


--
-- Name: reminders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reminders (
    id bigint NOT NULL,
    fragment_id bigint,
    user_id bigint NOT NULL,
    due_at timestamp(0) without time zone NOT NULL,
    message character varying(512),
    state json,
    completed_at timestamp(0) without time zone
);


--
-- Name: reminders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reminders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reminders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reminders_id_seq OWNED BY public.reminders.id;


--
-- Name: saved_queries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.saved_queries (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    entity character varying(255) NOT NULL,
    filters json NOT NULL,
    boosts json,
    order_by json,
    "limit" integer,
    owner_id uuid,
    visibility character varying(255) DEFAULT 'private'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: schedule_metrics_daily; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.schedule_metrics_daily (
    day date NOT NULL,
    runs integer DEFAULT 0 NOT NULL,
    failures integer DEFAULT 0 NOT NULL,
    duration_ms_sum bigint DEFAULT '0'::bigint NOT NULL,
    duration_ms_count integer DEFAULT 0 NOT NULL
);


--
-- Name: schedule_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.schedule_runs (
    id bigint NOT NULL,
    schedule_id bigint NOT NULL,
    planned_run_at timestamp(0) without time zone NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    output text,
    error_message text,
    duration_ms integer,
    job_id character varying(255),
    dedupe_key character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT schedule_runs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'running'::character varying, 'completed'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: schedule_runs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.schedule_runs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: schedule_runs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.schedule_runs_id_seq OWNED BY public.schedule_runs.id;


--
-- Name: schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.schedules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    command_slug character varying(255) NOT NULL,
    payload json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    recurrence_type character varying(255) DEFAULT 'one_off'::character varying NOT NULL,
    recurrence_value character varying(255),
    timezone character varying(255) DEFAULT 'UTC'::character varying NOT NULL,
    next_run_at timestamp(0) without time zone,
    last_run_at timestamp(0) without time zone,
    locked_at timestamp(0) without time zone,
    lock_owner character varying(255),
    last_tick_at timestamp(0) without time zone,
    run_count integer DEFAULT 0 NOT NULL,
    max_runs integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT schedules_recurrence_type_check CHECK (((recurrence_type)::text = ANY ((ARRAY['one_off'::character varying, 'daily_at'::character varying, 'weekly_at'::character varying, 'cron_expr'::character varying])::text[]))),
    CONSTRAINT schedules_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'paused'::character varying, 'completed'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.schedules_id_seq OWNED BY public.schedules.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: sources; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sources (
    id bigint NOT NULL,
    key character varying(64) NOT NULL,
    label character varying(128) NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN sources.key; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sources.key IS 'Source key: obsidian, web, clipboard, youtube, etc.';


--
-- Name: COLUMN sources.label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sources.label IS 'Human readable label';


--
-- Name: COLUMN sources.meta; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.sources.meta IS 'Source-specific metadata';


--
-- Name: sources_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sources_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sources_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sources_id_seq OWNED BY public.sources.id;


--
-- Name: sprint_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sprint_items (
    id uuid NOT NULL,
    sprint_id uuid NOT NULL,
    work_item_id uuid NOT NULL,
    "position" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sprints; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sprints (
    id uuid NOT NULL,
    code character varying(255) NOT NULL,
    starts_on date,
    ends_on date,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_alerts (
    id bigint NOT NULL,
    alert_name character varying(100) NOT NULL,
    component character varying(100) NOT NULL,
    condition_type character varying(50) NOT NULL,
    condition_config json NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    last_triggered_at timestamp(0) without time zone,
    trigger_count integer DEFAULT 0 NOT NULL,
    notification_config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_alerts_id_seq OWNED BY public.telemetry_alerts.id;


--
-- Name: telemetry_correlation_chains; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_correlation_chains (
    id bigint NOT NULL,
    chain_id character varying(255) NOT NULL,
    root_correlation_id character varying(255) NOT NULL,
    depth integer NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    completed_at timestamp(0) without time zone,
    total_events integer DEFAULT 0 NOT NULL,
    chain_metadata json,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_correlation_chains_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_correlation_chains_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_correlation_chains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_correlation_chains_id_seq OWNED BY public.telemetry_correlation_chains.id;


--
-- Name: telemetry_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_events (
    id bigint NOT NULL,
    correlation_id character varying(255) NOT NULL,
    event_type character varying(50) NOT NULL,
    event_name character varying(100) NOT NULL,
    "timestamp" timestamp(0) without time zone NOT NULL,
    component character varying(100) NOT NULL,
    operation character varying(100),
    metadata json,
    context json,
    performance json,
    message text,
    level character varying(20) DEFAULT 'info'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_events_id_seq OWNED BY public.telemetry_events.id;


--
-- Name: telemetry_health_checks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_health_checks (
    id bigint NOT NULL,
    component character varying(100) NOT NULL,
    check_name character varying(100) NOT NULL,
    is_healthy boolean NOT NULL,
    error_message text,
    response_time_ms numeric(10,3),
    check_metadata json,
    checked_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_health_checks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_health_checks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_health_checks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_health_checks_id_seq OWNED BY public.telemetry_health_checks.id;


--
-- Name: telemetry_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_metrics (
    id bigint NOT NULL,
    metric_name character varying(100) NOT NULL,
    component character varying(100) NOT NULL,
    metric_type character varying(20) NOT NULL,
    value numeric(20,6) NOT NULL,
    labels json,
    "timestamp" timestamp(0) without time zone NOT NULL,
    aggregation_period character varying(20) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_metrics_id_seq OWNED BY public.telemetry_metrics.id;


--
-- Name: telemetry_performance_snapshots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telemetry_performance_snapshots (
    id bigint NOT NULL,
    component character varying(100) NOT NULL,
    operation character varying(100) NOT NULL,
    duration_ms numeric(10,3) NOT NULL,
    memory_usage_bytes bigint,
    cpu_usage_percent integer,
    resource_metrics json,
    performance_class character varying(20) NOT NULL,
    recorded_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: telemetry_performance_snapshots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telemetry_performance_snapshots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telemetry_performance_snapshots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telemetry_performance_snapshots_id_seq OWNED BY public.telemetry_performance_snapshots.id;


--
-- Name: thumbnails; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.thumbnails (
    fragment_id bigint NOT NULL,
    kind character varying(255) NOT NULL,
    uri text NOT NULL,
    CONSTRAINT thumbnails_kind_check CHECK (((kind)::text = ANY (ARRAY[('small'::character varying)::text, ('medium'::character varying)::text, ('large'::character varying)::text])))
);


--
-- Name: todos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.todos (
    fragment_id bigint NOT NULL,
    title character varying(255),
    state json
);


--
-- Name: COLUMN todos.state; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.todos.state IS 'Rich state data: due_at, priority, status, etc.';


--
-- Name: tool_activity; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tool_activity (
    id uuid NOT NULL,
    tool character varying(255) NOT NULL,
    invocation_id uuid,
    status character varying(255) DEFAULT 'ok'::character varying NOT NULL,
    duration_ms bigint,
    command_slug character varying(255),
    fragment_id bigint,
    user_id bigint,
    ts timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: tool_invocations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tool_invocations (
    id uuid NOT NULL,
    user_id bigint,
    tool_slug character varying(255) NOT NULL,
    command_slug character varying(255),
    fragment_id bigint,
    request json,
    response json,
    status character varying(255) DEFAULT 'ok'::character varying NOT NULL,
    duration_ms double precision,
    created_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: tool_metrics_daily; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tool_metrics_daily (
    day date NOT NULL,
    tool character varying(255) NOT NULL,
    invocations integer DEFAULT 0 NOT NULL,
    errors integer DEFAULT 0 NOT NULL,
    duration_ms_sum bigint DEFAULT '0'::bigint NOT NULL,
    duration_ms_count integer DEFAULT 0 NOT NULL
);


--
-- Name: triggers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.triggers (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    kind character varying(255) NOT NULL,
    spec character varying(255),
    payload json,
    next_run_at timestamp(0) without time zone,
    last_run_at timestamp(0) without time zone,
    state json,
    CONSTRAINT triggers_kind_check CHECK (((kind)::text = ANY (ARRAY[('cron'::character varying)::text, ('interval'::character varying)::text, ('event'::character varying)::text])))
);


--
-- Name: COLUMN triggers.spec; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.triggers.spec IS 'Cron string or rule key';


--
-- Name: COLUMN triggers.payload; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.triggers.payload IS 'What to run';


--
-- Name: triggers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.triggers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: triggers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.triggers_id_seq OWNED BY public.triggers.id;


--
-- Name: types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.types (
    id bigint NOT NULL,
    value character varying(255) NOT NULL,
    label character varying(255) NOT NULL,
    color character varying(255) DEFAULT 'gray'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.types_id_seq OWNED BY public.types.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    toast_verbosity character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    display_name character varying(255),
    avatar_path character varying(255),
    use_gravatar boolean DEFAULT true NOT NULL,
    profile_settings json,
    profile_completed_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vault_routing_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vault_routing_rules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    match_type character varying(255) DEFAULT 'keyword'::character varying NOT NULL,
    match_value character varying(255),
    conditions json,
    target_vault_id bigint NOT NULL,
    target_project_id bigint,
    scope_vault_id bigint,
    scope_project_id bigint,
    priority integer DEFAULT 100 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vault_routing_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vault_routing_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vault_routing_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vault_routing_rules_id_seq OWNED BY public.vault_routing_rules.id;


--
-- Name: vaults; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vaults (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_default boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vaults_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vaults_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vaults_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vaults_id_seq OWNED BY public.vaults.id;


--
-- Name: work_item_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.work_item_events (
    id uuid NOT NULL,
    work_item_id uuid NOT NULL,
    kind character varying(255) NOT NULL,
    body text,
    meta json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: work_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.work_items (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    parent_id uuid,
    assignee_type character varying(255),
    assignee_id uuid,
    status character varying(255) DEFAULT 'backlog'::character varying NOT NULL,
    priority character varying(255),
    project_id uuid,
    tags json,
    state json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: a_i_credentials id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.a_i_credentials ALTER COLUMN id SET DEFAULT nextval('public.a_i_credentials_id_seq'::regclass);


--
-- Name: articles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.articles ALTER COLUMN id SET DEFAULT nextval('public.articles_id_seq'::regclass);


--
-- Name: bookmarks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks ALTER COLUMN id SET DEFAULT nextval('public.bookmarks_id_seq'::regclass);


--
-- Name: builds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.builds ALTER COLUMN id SET DEFAULT nextval('public.builds_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: chat_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_sessions ALTER COLUMN id SET DEFAULT nextval('public.chat_sessions_id_seq'::regclass);


--
-- Name: collections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections ALTER COLUMN id SET DEFAULT nextval('public.collections_id_seq'::regclass);


--
-- Name: command_registry id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.command_registry ALTER COLUMN id SET DEFAULT nextval('public.command_registry_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fragment_embeddings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_embeddings ALTER COLUMN id SET DEFAULT nextval('public.fragment_embeddings_id_seq'::regclass);


--
-- Name: fragment_links id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_links ALTER COLUMN id SET DEFAULT nextval('public.fragment_links_id_seq'::regclass);


--
-- Name: fragment_type_registry id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_type_registry ALTER COLUMN id SET DEFAULT nextval('public.fragment_type_registry_id_seq'::regclass);


--
-- Name: fragments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments ALTER COLUMN id SET DEFAULT nextval('public.fragments_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: object_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.object_types ALTER COLUMN id SET DEFAULT nextval('public.object_types_id_seq'::regclass);


--
-- Name: projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);


--
-- Name: recall_decisions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recall_decisions ALTER COLUMN id SET DEFAULT nextval('public.recall_decisions_id_seq'::regclass);


--
-- Name: reminders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminders ALTER COLUMN id SET DEFAULT nextval('public.reminders_id_seq'::regclass);


--
-- Name: schedule_runs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedule_runs ALTER COLUMN id SET DEFAULT nextval('public.schedule_runs_id_seq'::regclass);


--
-- Name: schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedules ALTER COLUMN id SET DEFAULT nextval('public.schedules_id_seq'::regclass);


--
-- Name: sources id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sources ALTER COLUMN id SET DEFAULT nextval('public.sources_id_seq'::regclass);


--
-- Name: telemetry_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_alerts ALTER COLUMN id SET DEFAULT nextval('public.telemetry_alerts_id_seq'::regclass);


--
-- Name: telemetry_correlation_chains id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_correlation_chains ALTER COLUMN id SET DEFAULT nextval('public.telemetry_correlation_chains_id_seq'::regclass);


--
-- Name: telemetry_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_events ALTER COLUMN id SET DEFAULT nextval('public.telemetry_events_id_seq'::regclass);


--
-- Name: telemetry_health_checks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_health_checks ALTER COLUMN id SET DEFAULT nextval('public.telemetry_health_checks_id_seq'::regclass);


--
-- Name: telemetry_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_metrics ALTER COLUMN id SET DEFAULT nextval('public.telemetry_metrics_id_seq'::regclass);


--
-- Name: telemetry_performance_snapshots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_performance_snapshots ALTER COLUMN id SET DEFAULT nextval('public.telemetry_performance_snapshots_id_seq'::regclass);


--
-- Name: triggers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.triggers ALTER COLUMN id SET DEFAULT nextval('public.triggers_id_seq'::regclass);


--
-- Name: types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.types ALTER COLUMN id SET DEFAULT nextval('public.types_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vault_routing_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules ALTER COLUMN id SET DEFAULT nextval('public.vault_routing_rules_id_seq'::regclass);


--
-- Name: vaults id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vaults ALTER COLUMN id SET DEFAULT nextval('public.vaults_id_seq'::regclass);


--
-- Name: a_i_credentials a_i_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.a_i_credentials
    ADD CONSTRAINT a_i_credentials_pkey PRIMARY KEY (id);


--
-- Name: a_i_credentials a_i_credentials_provider_credential_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.a_i_credentials
    ADD CONSTRAINT a_i_credentials_provider_credential_type_unique UNIQUE (provider, credential_type);


--
-- Name: agent_decisions agent_decisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_decisions
    ADD CONSTRAINT agent_decisions_pkey PRIMARY KEY (id);


--
-- Name: agent_notes agent_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_notes
    ADD CONSTRAINT agent_notes_pkey PRIMARY KEY (id);


--
-- Name: agent_vectors agent_vectors_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_vectors
    ADD CONSTRAINT agent_vectors_key_unique UNIQUE (key);


--
-- Name: agent_vectors agent_vectors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_vectors
    ADD CONSTRAINT agent_vectors_pkey PRIMARY KEY (id);


--
-- Name: article_fragments article_fragments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.article_fragments
    ADD CONSTRAINT article_fragments_pkey PRIMARY KEY (article_id, order_pos);


--
-- Name: articles articles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.articles
    ADD CONSTRAINT articles_pkey PRIMARY KEY (id);


--
-- Name: artifacts artifacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.artifacts
    ADD CONSTRAINT artifacts_pkey PRIMARY KEY (id);


--
-- Name: bookmarks bookmarks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks
    ADD CONSTRAINT bookmarks_pkey PRIMARY KEY (id);


--
-- Name: builds builds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.builds
    ADD CONSTRAINT builds_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: calendar_events calendar_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendar_events
    ADD CONSTRAINT calendar_events_pkey PRIMARY KEY (fragment_id);


--
-- Name: categories categories_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_name_unique UNIQUE (name);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: chat_sessions chat_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_pkey PRIMARY KEY (id);


--
-- Name: chat_sessions chat_sessions_short_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_short_code_unique UNIQUE (short_code);


--
-- Name: collection_items collection_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_items
    ADD CONSTRAINT collection_items_pkey PRIMARY KEY (collection_id, order_pos);


--
-- Name: collections collections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT collections_pkey PRIMARY KEY (id);


--
-- Name: command_activity command_activity_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.command_activity
    ADD CONSTRAINT command_activity_pkey PRIMARY KEY (id);


--
-- Name: command_registry command_registry_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.command_registry
    ADD CONSTRAINT command_registry_pkey PRIMARY KEY (id);


--
-- Name: command_registry command_registry_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.command_registry
    ADD CONSTRAINT command_registry_slug_unique UNIQUE (slug);


--
-- Name: command_runs command_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.command_runs
    ADD CONSTRAINT command_runs_pkey PRIMARY KEY (id);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (fragment_id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: file_text file_text_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file_text
    ADD CONSTRAINT file_text_pkey PRIMARY KEY (fragment_id);


--
-- Name: files files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_pkey PRIMARY KEY (fragment_id);


--
-- Name: fragment_embeddings fragment_embeddings_fragment_id_provider_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_embeddings
    ADD CONSTRAINT fragment_embeddings_fragment_id_provider_unique UNIQUE (fragment_id, provider);


--
-- Name: fragment_embeddings fragment_embeddings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_embeddings
    ADD CONSTRAINT fragment_embeddings_pkey PRIMARY KEY (id);


--
-- Name: fragment_links fragment_links_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_links
    ADD CONSTRAINT fragment_links_pkey PRIMARY KEY (id);


--
-- Name: fragment_metrics_daily fragment_metrics_daily_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_metrics_daily
    ADD CONSTRAINT fragment_metrics_daily_pkey PRIMARY KEY (day, type);


--
-- Name: fragment_tags fragment_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_tags
    ADD CONSTRAINT fragment_tags_pkey PRIMARY KEY (fragment_id, tag);


--
-- Name: fragment_type_registry fragment_type_registry_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_type_registry
    ADD CONSTRAINT fragment_type_registry_pkey PRIMARY KEY (id);


--
-- Name: fragment_type_registry fragment_type_registry_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_type_registry
    ADD CONSTRAINT fragment_type_registry_slug_unique UNIQUE (slug);


--
-- Name: fragments fragments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments
    ADD CONSTRAINT fragments_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: links links_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_pkey PRIMARY KEY (fragment_id);


--
-- Name: meetings meetings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_pkey PRIMARY KEY (fragment_id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: object_types object_types_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.object_types
    ADD CONSTRAINT object_types_key_unique UNIQUE (key);


--
-- Name: object_types object_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.object_types
    ADD CONSTRAINT object_types_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: prompt_registry prompt_registry_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prompt_registry
    ADD CONSTRAINT prompt_registry_pkey PRIMARY KEY (id);


--
-- Name: recall_decisions recall_decisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recall_decisions
    ADD CONSTRAINT recall_decisions_pkey PRIMARY KEY (id);


--
-- Name: reminders reminders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_pkey PRIMARY KEY (id);


--
-- Name: saved_queries saved_queries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.saved_queries
    ADD CONSTRAINT saved_queries_pkey PRIMARY KEY (id);


--
-- Name: schedule_metrics_daily schedule_metrics_daily_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedule_metrics_daily
    ADD CONSTRAINT schedule_metrics_daily_pkey PRIMARY KEY (day);


--
-- Name: schedule_runs schedule_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedule_runs
    ADD CONSTRAINT schedule_runs_pkey PRIMARY KEY (id);


--
-- Name: schedule_runs schedule_runs_schedule_id_planned_run_at_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedule_runs
    ADD CONSTRAINT schedule_runs_schedule_id_planned_run_at_unique UNIQUE (schedule_id, planned_run_at);


--
-- Name: schedules schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedules
    ADD CONSTRAINT schedules_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sources sources_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sources
    ADD CONSTRAINT sources_key_unique UNIQUE (key);


--
-- Name: sources sources_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sources
    ADD CONSTRAINT sources_pkey PRIMARY KEY (id);


--
-- Name: sprint_items sprint_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sprint_items
    ADD CONSTRAINT sprint_items_pkey PRIMARY KEY (id);


--
-- Name: sprints sprints_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sprints
    ADD CONSTRAINT sprints_code_unique UNIQUE (code);


--
-- Name: sprints sprints_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sprints
    ADD CONSTRAINT sprints_pkey PRIMARY KEY (id);


--
-- Name: telemetry_alerts telemetry_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_alerts
    ADD CONSTRAINT telemetry_alerts_pkey PRIMARY KEY (id);


--
-- Name: telemetry_correlation_chains telemetry_correlation_chains_chain_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_correlation_chains
    ADD CONSTRAINT telemetry_correlation_chains_chain_id_unique UNIQUE (chain_id);


--
-- Name: telemetry_correlation_chains telemetry_correlation_chains_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_correlation_chains
    ADD CONSTRAINT telemetry_correlation_chains_pkey PRIMARY KEY (id);


--
-- Name: telemetry_events telemetry_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_events
    ADD CONSTRAINT telemetry_events_pkey PRIMARY KEY (id);


--
-- Name: telemetry_health_checks telemetry_health_checks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_health_checks
    ADD CONSTRAINT telemetry_health_checks_pkey PRIMARY KEY (id);


--
-- Name: telemetry_metrics telemetry_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_metrics
    ADD CONSTRAINT telemetry_metrics_pkey PRIMARY KEY (id);


--
-- Name: telemetry_performance_snapshots telemetry_performance_snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telemetry_performance_snapshots
    ADD CONSTRAINT telemetry_performance_snapshots_pkey PRIMARY KEY (id);


--
-- Name: thumbnails thumbnails_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thumbnails
    ADD CONSTRAINT thumbnails_pkey PRIMARY KEY (fragment_id, kind);


--
-- Name: todos todos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.todos
    ADD CONSTRAINT todos_pkey PRIMARY KEY (fragment_id);


--
-- Name: tool_activity tool_activity_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tool_activity
    ADD CONSTRAINT tool_activity_pkey PRIMARY KEY (id);


--
-- Name: tool_invocations tool_invocations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tool_invocations
    ADD CONSTRAINT tool_invocations_pkey PRIMARY KEY (id);


--
-- Name: tool_metrics_daily tool_metrics_daily_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tool_metrics_daily
    ADD CONSTRAINT tool_metrics_daily_pkey PRIMARY KEY (day, tool);


--
-- Name: triggers triggers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.triggers
    ADD CONSTRAINT triggers_pkey PRIMARY KEY (id);


--
-- Name: types types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.types
    ADD CONSTRAINT types_pkey PRIMARY KEY (id);


--
-- Name: types types_value_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.types
    ADD CONSTRAINT types_value_unique UNIQUE (value);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: vault_routing_rules vault_routing_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules
    ADD CONSTRAINT vault_routing_rules_pkey PRIMARY KEY (id);


--
-- Name: vaults vaults_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vaults
    ADD CONSTRAINT vaults_pkey PRIMARY KEY (id);


--
-- Name: work_item_events work_item_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.work_item_events
    ADD CONSTRAINT work_item_events_pkey PRIMARY KEY (id);


--
-- Name: work_items work_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.work_items
    ADD CONSTRAINT work_items_pkey PRIMARY KEY (id);


--
-- Name: a_i_credentials_provider_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX a_i_credentials_provider_index ON public.a_i_credentials USING btree (provider);


--
-- Name: agent_notes_agent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_notes_agent_id_index ON public.agent_notes USING btree (agent_id);


--
-- Name: article_fragments_fragment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX article_fragments_fragment_id_index ON public.article_fragments USING btree (fragment_id);


--
-- Name: articles_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX articles_status_index ON public.articles USING btree (status);


--
-- Name: articles_workspace_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX articles_workspace_id_index ON public.articles USING btree (workspace_id);


--
-- Name: artifacts_owner_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX artifacts_owner_id_index ON public.artifacts USING btree (owner_id);


--
-- Name: artifacts_source_query_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX artifacts_source_query_id_index ON public.artifacts USING btree (source_query_id);


--
-- Name: bookmarks_scope_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bookmarks_scope_idx ON public.bookmarks USING btree (vault_id, project_id, last_viewed_at);


--
-- Name: builds_workspace_id_range_start_kind_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX builds_workspace_id_range_start_kind_index ON public.builds USING btree (workspace_id, range_start, kind);


--
-- Name: calendar_events_ends_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX calendar_events_ends_at_index ON public.calendar_events USING btree (ends_at);


--
-- Name: calendar_events_starts_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX calendar_events_starts_at_index ON public.calendar_events USING btree (starts_at);


--
-- Name: chat_sessions_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_is_active_index ON public.chat_sessions USING btree (is_active);


--
-- Name: chat_sessions_is_pinned_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_is_pinned_sort_order_index ON public.chat_sessions USING btree (is_pinned, sort_order);


--
-- Name: chat_sessions_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_last_activity_at_index ON public.chat_sessions USING btree (last_activity_at);


--
-- Name: chat_sessions_short_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_short_code_index ON public.chat_sessions USING btree (short_code);


--
-- Name: chat_sessions_vault_id_is_active_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_vault_id_is_active_last_activity_at_index ON public.chat_sessions USING btree (vault_id, is_active, last_activity_at);


--
-- Name: chat_sessions_vault_id_project_id_is_pinned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_sessions_vault_id_project_id_is_pinned_index ON public.chat_sessions USING btree (vault_id, project_id, is_pinned);


--
-- Name: collection_items_object_type_object_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collection_items_object_type_object_id_index ON public.collection_items USING btree (object_type, object_id);


--
-- Name: command_activity_slug_ts_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX command_activity_slug_ts_index ON public.command_activity USING btree (slug, ts);


--
-- Name: command_registry_reserved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX command_registry_reserved_index ON public.command_registry USING btree (reserved);


--
-- Name: command_registry_slug_version_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX command_registry_slug_version_index ON public.command_registry USING btree (slug, version);


--
-- Name: command_registry_steps_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX command_registry_steps_hash_index ON public.command_registry USING btree (steps_hash);


--
-- Name: command_runs_slug_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX command_runs_slug_started_at_index ON public.command_runs USING btree (slug, started_at);


--
-- Name: files_content_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_content_hash_index ON public.files USING btree (content_hash);


--
-- Name: files_mime_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_mime_index ON public.files USING btree (mime);


--
-- Name: fragment_embeddings_content_hash_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_embeddings_content_hash_idx ON public.fragment_embeddings USING btree (content_hash);


--
-- Name: fragment_embeddings_fragment_provider_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_embeddings_fragment_provider_idx ON public.fragment_embeddings USING btree (fragment_id, provider);


--
-- Name: fragment_embeddings_hnsw_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_embeddings_hnsw_idx ON public.fragment_embeddings USING hnsw (embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: fragment_embeddings_model_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_embeddings_model_idx ON public.fragment_embeddings USING btree (model);


--
-- Name: fragment_embeddings_provider_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_embeddings_provider_idx ON public.fragment_embeddings USING btree (provider);


--
-- Name: fragment_embeddings_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX fragment_embeddings_unique ON public.fragment_embeddings USING btree (fragment_id, provider, model, content_hash);


--
-- Name: fragment_links_from_id_relation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_links_from_id_relation_index ON public.fragment_links USING btree (from_id, relation);


--
-- Name: fragment_links_to_id_relation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_links_to_id_relation_index ON public.fragment_links USING btree (to_id, relation);


--
-- Name: fragment_tags_tag_fragment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_tags_tag_fragment_id_index ON public.fragment_tags USING btree (tag, fragment_id);


--
-- Name: fragment_type_registry_schema_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_type_registry_schema_hash_index ON public.fragment_type_registry USING btree (schema_hash);


--
-- Name: fragment_type_registry_slug_version_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragment_type_registry_slug_version_index ON public.fragment_type_registry USING btree (slug, version);


--
-- Name: fragments_deleted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_deleted_at_index ON public.fragments USING btree (deleted_at);


--
-- Name: fragments_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_hash_index ON public.fragments USING btree (hash);


--
-- Name: fragments_importance_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_importance_index ON public.fragments USING btree (importance);


--
-- Name: fragments_inbox_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_inbox_status_index ON public.fragments USING btree (inbox_status);


--
-- Name: fragments_mime_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_mime_index ON public.fragments USING btree (mime);


--
-- Name: fragments_object_type_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_object_type_id_index ON public.fragments USING btree (object_type_id);


--
-- Name: fragments_pinned_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_pinned_index ON public.fragments USING btree (pinned);


--
-- Name: fragments_project_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_project_id_created_at_index ON public.fragments USING btree (project_id, created_at);


--
-- Name: fragments_provider_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_provider_created_idx ON public.fragments USING btree (((metadata ->> 'provider'::text)), created_at);


--
-- Name: fragments_session_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_session_created_idx ON public.fragments USING btree (((metadata ->> 'session_id'::text)), created_at);


--
-- Name: fragments_source_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_source_key_index ON public.fragments USING btree (source_key);


--
-- Name: fragments_turn_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_turn_created_idx ON public.fragments USING btree (((metadata ->> 'turn'::text)), created_at);


--
-- Name: fragments_vault_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_vault_project_id_index ON public.fragments USING btree (vault, project_id);


--
-- Name: fragments_workspace_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_workspace_id_index ON public.fragments USING btree (workspace_id);


--
-- Name: idx_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_created_at ON public.fragments USING btree (created_at);


--
-- Name: idx_frag_category_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_frag_category_created ON public.fragments USING btree (category_id, created_at);


--
-- Name: idx_frag_fulltext; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_frag_fulltext ON public.fragments USING gin (to_tsvector('simple'::regconfig, (((COALESCE(title, ''::character varying))::text || ' '::text) || COALESCE(edited_message, message, ''::text))));


--
-- Name: idx_frag_importance_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_frag_importance_created ON public.fragments USING btree (importance, created_at);


--
-- Name: idx_frag_vault_type_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_frag_vault_type_created ON public.fragments USING btree (vault, type, created_at);


--
-- Name: idx_frag_workspace_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_frag_workspace_created ON public.fragments USING btree (workspace_id, created_at);


--
-- Name: idx_fragments_hash_bucket; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_hash_bucket ON public.fragments USING btree (input_hash, hash_bucket);


--
-- Name: idx_fragments_inbox_pending_type_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_inbox_pending_type_created ON public.fragments USING btree (type, created_at) WHERE ((inbox_status)::text = 'pending'::text);


--
-- Name: idx_fragments_todo_due_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_todo_due_date ON public.fragments USING btree ((((state)::jsonb ->> 'due_at'::text))) WHERE (((type)::text = 'todo'::text) AND (((state)::jsonb ->> 'due_at'::text) IS NOT NULL));


--
-- Name: idx_fragments_todo_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_todo_priority ON public.fragments USING btree ((((state)::jsonb ->> 'priority'::text))) WHERE ((type)::text = 'todo'::text);


--
-- Name: idx_fragments_todo_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_todo_status ON public.fragments USING btree ((((state)::jsonb ->> 'status'::text))) WHERE ((type)::text = 'todo'::text);


--
-- Name: idx_fragments_todo_status_priority; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fragments_todo_status_priority ON public.fragments USING btree ((((state)::jsonb ->> 'status'::text)), (((state)::jsonb ->> 'priority'::text))) WHERE ((type)::text = 'todo'::text);


--
-- Name: idx_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_type ON public.fragments USING btree (type);


--
-- Name: idx_vault_project; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_vault_project ON public.fragments USING btree (vault, project_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: links_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX links_domain_index ON public.links USING btree (domain);


--
-- Name: projects_vault_id_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_vault_id_is_default_index ON public.projects USING btree (vault_id, is_default);


--
-- Name: projects_vault_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_vault_id_sort_order_index ON public.projects USING btree (vault_id, sort_order);


--
-- Name: prompt_registry_owner_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX prompt_registry_owner_id_index ON public.prompt_registry USING btree (owner_id);


--
-- Name: recall_decisions_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recall_decisions_action_index ON public.recall_decisions USING btree (action);


--
-- Name: recall_decisions_decided_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recall_decisions_decided_at_index ON public.recall_decisions USING btree (decided_at);


--
-- Name: recall_decisions_selected_fragment_id_decided_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recall_decisions_selected_fragment_id_decided_at_index ON public.recall_decisions USING btree (selected_fragment_id, decided_at);


--
-- Name: recall_decisions_user_id_decided_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX recall_decisions_user_id_decided_at_index ON public.recall_decisions USING btree (user_id, decided_at);


--
-- Name: reminders_user_id_due_at_completed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reminders_user_id_due_at_completed_at_index ON public.reminders USING btree (user_id, due_at, completed_at);


--
-- Name: saved_queries_owner_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX saved_queries_owner_id_index ON public.saved_queries USING btree (owner_id);


--
-- Name: schedule_runs_dedupe_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX schedule_runs_dedupe_key_index ON public.schedule_runs USING btree (dedupe_key);


--
-- Name: schedule_runs_status_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX schedule_runs_status_started_at_index ON public.schedule_runs USING btree (status, started_at);


--
-- Name: schedules_command_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX schedules_command_slug_index ON public.schedules USING btree (command_slug);


--
-- Name: schedules_locked_at_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX schedules_locked_at_status_index ON public.schedules USING btree (locked_at, status);


--
-- Name: schedules_status_next_run_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX schedules_status_next_run_at_index ON public.schedules USING btree (status, next_run_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: sprint_items_sprint_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sprint_items_sprint_id_index ON public.sprint_items USING btree (sprint_id);


--
-- Name: sprint_items_work_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sprint_items_work_item_id_index ON public.sprint_items USING btree (work_item_id);


--
-- Name: telemetry_alerts_alert_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_alerts_alert_name_index ON public.telemetry_alerts USING btree (alert_name);


--
-- Name: telemetry_alerts_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_alerts_component_index ON public.telemetry_alerts USING btree (component);


--
-- Name: telemetry_alerts_status_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_alerts_status_component_index ON public.telemetry_alerts USING btree (status, component);


--
-- Name: telemetry_alerts_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_alerts_status_index ON public.telemetry_alerts USING btree (status);


--
-- Name: telemetry_correlation_chains_depth_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_correlation_chains_depth_index ON public.telemetry_correlation_chains USING btree (depth);


--
-- Name: telemetry_correlation_chains_root_correlation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_correlation_chains_root_correlation_id_index ON public.telemetry_correlation_chains USING btree (root_correlation_id);


--
-- Name: telemetry_correlation_chains_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_correlation_chains_started_at_index ON public.telemetry_correlation_chains USING btree (started_at);


--
-- Name: telemetry_correlation_chains_started_at_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_correlation_chains_started_at_status_index ON public.telemetry_correlation_chains USING btree (started_at, status);


--
-- Name: telemetry_correlation_chains_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_correlation_chains_status_index ON public.telemetry_correlation_chains USING btree (status);


--
-- Name: telemetry_events_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_component_index ON public.telemetry_events USING btree (component);


--
-- Name: telemetry_events_component_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_component_timestamp_index ON public.telemetry_events USING btree (component, "timestamp");


--
-- Name: telemetry_events_correlation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_correlation_id_index ON public.telemetry_events USING btree (correlation_id);


--
-- Name: telemetry_events_correlation_id_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_correlation_id_timestamp_index ON public.telemetry_events USING btree (correlation_id, "timestamp");


--
-- Name: telemetry_events_event_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_event_name_index ON public.telemetry_events USING btree (event_name);


--
-- Name: telemetry_events_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_event_type_index ON public.telemetry_events USING btree (event_type);


--
-- Name: telemetry_events_event_type_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_event_type_timestamp_index ON public.telemetry_events USING btree (event_type, "timestamp");


--
-- Name: telemetry_events_level_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_level_index ON public.telemetry_events USING btree (level);


--
-- Name: telemetry_events_level_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_level_timestamp_index ON public.telemetry_events USING btree (level, "timestamp");


--
-- Name: telemetry_events_operation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_operation_index ON public.telemetry_events USING btree (operation);


--
-- Name: telemetry_events_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_events_timestamp_index ON public.telemetry_events USING btree ("timestamp");


--
-- Name: telemetry_health_checks_check_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_check_name_index ON public.telemetry_health_checks USING btree (check_name);


--
-- Name: telemetry_health_checks_checked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_checked_at_index ON public.telemetry_health_checks USING btree (checked_at);


--
-- Name: telemetry_health_checks_component_checked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_component_checked_at_index ON public.telemetry_health_checks USING btree (component, checked_at);


--
-- Name: telemetry_health_checks_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_component_index ON public.telemetry_health_checks USING btree (component);


--
-- Name: telemetry_health_checks_is_healthy_checked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_is_healthy_checked_at_index ON public.telemetry_health_checks USING btree (is_healthy, checked_at);


--
-- Name: telemetry_health_checks_is_healthy_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_health_checks_is_healthy_index ON public.telemetry_health_checks USING btree (is_healthy);


--
-- Name: telemetry_metrics_aggregation_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_aggregation_period_index ON public.telemetry_metrics USING btree (aggregation_period);


--
-- Name: telemetry_metrics_aggregation_period_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_aggregation_period_timestamp_index ON public.telemetry_metrics USING btree (aggregation_period, "timestamp");


--
-- Name: telemetry_metrics_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_component_index ON public.telemetry_metrics USING btree (component);


--
-- Name: telemetry_metrics_component_metric_name_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_component_metric_name_timestamp_index ON public.telemetry_metrics USING btree (component, metric_name, "timestamp");


--
-- Name: telemetry_metrics_metric_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_metric_name_index ON public.telemetry_metrics USING btree (metric_name);


--
-- Name: telemetry_metrics_metric_name_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_metric_name_timestamp_index ON public.telemetry_metrics USING btree (metric_name, "timestamp");


--
-- Name: telemetry_metrics_metric_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_metric_type_index ON public.telemetry_metrics USING btree (metric_type);


--
-- Name: telemetry_metrics_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_metrics_timestamp_index ON public.telemetry_metrics USING btree ("timestamp");


--
-- Name: telemetry_performance_snapshots_component_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_component_index ON public.telemetry_performance_snapshots USING btree (component);


--
-- Name: telemetry_performance_snapshots_component_operation_recorded_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_component_operation_recorded_at ON public.telemetry_performance_snapshots USING btree (component, operation, recorded_at);


--
-- Name: telemetry_performance_snapshots_operation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_operation_index ON public.telemetry_performance_snapshots USING btree (operation);


--
-- Name: telemetry_performance_snapshots_performance_class_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_performance_class_index ON public.telemetry_performance_snapshots USING btree (performance_class);


--
-- Name: telemetry_performance_snapshots_performance_class_recorded_at_i; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_performance_class_recorded_at_i ON public.telemetry_performance_snapshots USING btree (performance_class, recorded_at);


--
-- Name: telemetry_performance_snapshots_recorded_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telemetry_performance_snapshots_recorded_at_index ON public.telemetry_performance_snapshots USING btree (recorded_at);


--
-- Name: tool_activity_tool_ts_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tool_activity_tool_ts_index ON public.tool_activity USING btree (tool, ts);


--
-- Name: tool_invocations_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tool_invocations_status_created_at_index ON public.tool_invocations USING btree (status, created_at);


--
-- Name: tool_invocations_tool_slug_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tool_invocations_tool_slug_created_at_index ON public.tool_invocations USING btree (tool_slug, created_at);


--
-- Name: tool_invocations_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tool_invocations_user_id_created_at_index ON public.tool_invocations USING btree (user_id, created_at);


--
-- Name: triggers_user_id_next_run_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX triggers_user_id_next_run_at_index ON public.triggers USING btree (user_id, next_run_at);


--
-- Name: users_profile_completed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_profile_completed_at_index ON public.users USING btree (profile_completed_at);


--
-- Name: users_use_gravatar_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_use_gravatar_index ON public.users USING btree (use_gravatar);


--
-- Name: vault_routing_rules_priority_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vault_routing_rules_priority_is_active_index ON public.vault_routing_rules USING btree (priority, is_active);


--
-- Name: vaults_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vaults_is_default_index ON public.vaults USING btree (is_default);


--
-- Name: vaults_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vaults_sort_order_index ON public.vaults USING btree (sort_order);


--
-- Name: work_item_events_work_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX work_item_events_work_item_id_index ON public.work_item_events USING btree (work_item_id);


--
-- Name: work_items_assignee_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX work_items_assignee_id_index ON public.work_items USING btree (assignee_id);


--
-- Name: work_items_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX work_items_parent_id_index ON public.work_items USING btree (parent_id);


--
-- Name: work_items_project_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX work_items_project_id_index ON public.work_items USING btree (project_id);


--
-- Name: article_fragments article_fragments_article_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.article_fragments
    ADD CONSTRAINT article_fragments_article_id_foreign FOREIGN KEY (article_id) REFERENCES public.articles(id) ON DELETE CASCADE;


--
-- Name: article_fragments article_fragments_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.article_fragments
    ADD CONSTRAINT article_fragments_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE SET NULL;


--
-- Name: bookmarks bookmarks_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks
    ADD CONSTRAINT bookmarks_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: bookmarks bookmarks_vault_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bookmarks
    ADD CONSTRAINT bookmarks_vault_id_foreign FOREIGN KEY (vault_id) REFERENCES public.vaults(id) ON DELETE CASCADE;


--
-- Name: builds builds_article_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.builds
    ADD CONSTRAINT builds_article_id_foreign FOREIGN KEY (article_id) REFERENCES public.articles(id) ON DELETE CASCADE;


--
-- Name: calendar_events calendar_events_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendar_events
    ADD CONSTRAINT calendar_events_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: chat_sessions chat_sessions_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: chat_sessions chat_sessions_vault_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_vault_id_foreign FOREIGN KEY (vault_id) REFERENCES public.vaults(id) ON DELETE SET NULL;


--
-- Name: collection_items collection_items_collection_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_items
    ADD CONSTRAINT collection_items_collection_id_foreign FOREIGN KEY (collection_id) REFERENCES public.collections(id) ON DELETE CASCADE;


--
-- Name: contacts contacts_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: file_text file_text_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file_text
    ADD CONSTRAINT file_text_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: files files_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: fragment_embeddings fragment_embeddings_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_embeddings
    ADD CONSTRAINT fragment_embeddings_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: fragment_links fragment_links_from_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_links
    ADD CONSTRAINT fragment_links_from_id_foreign FOREIGN KEY (from_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: fragment_links fragment_links_to_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_links
    ADD CONSTRAINT fragment_links_to_id_foreign FOREIGN KEY (to_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: fragment_tags fragment_tags_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_tags
    ADD CONSTRAINT fragment_tags_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: fragments fragments_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments
    ADD CONSTRAINT fragments_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: fragments fragments_object_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments
    ADD CONSTRAINT fragments_object_type_id_foreign FOREIGN KEY (object_type_id) REFERENCES public.object_types(id) ON DELETE SET NULL;


--
-- Name: fragments fragments_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments
    ADD CONSTRAINT fragments_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: fragments fragments_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragments
    ADD CONSTRAINT fragments_type_id_foreign FOREIGN KEY (type_id) REFERENCES public.types(id);


--
-- Name: links links_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.links
    ADD CONSTRAINT links_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: meetings meetings_calendar_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_calendar_event_id_foreign FOREIGN KEY (calendar_event_id) REFERENCES public.calendar_events(fragment_id) ON DELETE SET NULL;


--
-- Name: meetings meetings_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meetings
    ADD CONSTRAINT meetings_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: projects projects_vault_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_vault_id_foreign FOREIGN KEY (vault_id) REFERENCES public.vaults(id) ON DELETE CASCADE;


--
-- Name: recall_decisions recall_decisions_selected_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recall_decisions
    ADD CONSTRAINT recall_decisions_selected_fragment_id_foreign FOREIGN KEY (selected_fragment_id) REFERENCES public.fragments(id) ON DELETE SET NULL;


--
-- Name: recall_decisions recall_decisions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recall_decisions
    ADD CONSTRAINT recall_decisions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reminders reminders_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE SET NULL;


--
-- Name: reminders reminders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: schedule_runs schedule_runs_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schedule_runs
    ADD CONSTRAINT schedule_runs_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id) ON DELETE CASCADE;


--
-- Name: thumbnails thumbnails_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thumbnails
    ADD CONSTRAINT thumbnails_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: todos todos_fragment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.todos
    ADD CONSTRAINT todos_fragment_id_foreign FOREIGN KEY (fragment_id) REFERENCES public.fragments(id) ON DELETE CASCADE;


--
-- Name: triggers triggers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.triggers
    ADD CONSTRAINT triggers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: vault_routing_rules vault_routing_rules_scope_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules
    ADD CONSTRAINT vault_routing_rules_scope_project_id_foreign FOREIGN KEY (scope_project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: vault_routing_rules vault_routing_rules_scope_vault_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules
    ADD CONSTRAINT vault_routing_rules_scope_vault_id_foreign FOREIGN KEY (scope_vault_id) REFERENCES public.vaults(id) ON DELETE SET NULL;


--
-- Name: vault_routing_rules vault_routing_rules_target_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules
    ADD CONSTRAINT vault_routing_rules_target_project_id_foreign FOREIGN KEY (target_project_id) REFERENCES public.projects(id) ON DELETE SET NULL;


--
-- Name: vault_routing_rules vault_routing_rules_target_vault_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vault_routing_rules
    ADD CONSTRAINT vault_routing_rules_target_vault_id_foreign FOREIGN KEY (target_vault_id) REFERENCES public.vaults(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict lmBIy5kpjpPUevi4bOa140XSJYUo1tRU50pfdXE6pPBZJ7jkty1c5kijO5aaDfI
