--
-- PostgreSQL database dump
--

\restrict XInJbKuYc8u7fnLkCbhCZ0J7FQdSEy0ACl77oGVvIDOl5S4vTCejklHdMeO1HuB

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
-- Name: vector; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;


--
-- Name: EXTENSION vector; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION vector IS 'vector data type and ivfflat and hnsw access methods';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: article_fragments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.article_fragments (
    article_id bigint NOT NULL,
    fragment_id bigint,
    order_pos integer NOT NULL,
    body text,
    edit_mode character varying(255) DEFAULT 'reference'::character varying NOT NULL,
    CONSTRAINT article_fragments_edit_mode_check CHECK (((edit_mode)::text = ANY ((ARRAY['reference'::character varying, 'copy'::character varying])::text[])))
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
    CONSTRAINT articles_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'review'::character varying, 'published'::character varying])::text[])))
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
-- Name: bookmarks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bookmarks (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    fragment_ids json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    last_viewed_at timestamp(0) without time zone
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
    custom_name character varying(255)
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
    CONSTRAINT files_storage_kind_check CHECK (((storage_kind)::text = ANY ((ARRAY['local'::character varying, 'obsidian'::character varying, 'remote'::character varying, 's3'::character varying, 'gdrive'::character varying])::text[])))
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
    CONSTRAINT fragment_links_relation_check CHECK (((relation)::text = ANY ((ARRAY['similar_to'::character varying, 'refines'::character varying, 'cluster_member'::character varying, 'duplicate_of'::character varying, 'references'::character varying, 'mentions'::character varying, 'child_of'::character varying])::text[])))
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
-- Name: fragment_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fragment_tags (
    fragment_id bigint NOT NULL,
    tag character varying(128) NOT NULL
);


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
    edited_message text
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
-- Name: thumbnails; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.thumbnails (
    fragment_id bigint NOT NULL,
    kind character varying(255) NOT NULL,
    uri text NOT NULL,
    CONSTRAINT thumbnails_kind_check CHECK (((kind)::text = ANY ((ARRAY['small'::character varying, 'medium'::character varying, 'large'::character varying])::text[])))
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
    CONSTRAINT triggers_kind_check CHECK (((kind)::text = ANY ((ARRAY['cron'::character varying, 'interval'::character varying, 'event'::character varying])::text[])))
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
    updated_at timestamp(0) without time zone
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
-- Name: sources id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sources ALTER COLUMN id SET DEFAULT nextval('public.sources_id_seq'::regclass);


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
-- Name: fragment_tags fragment_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fragment_tags
    ADD CONSTRAINT fragment_tags_pkey PRIMARY KEY (fragment_id, tag);


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
-- Name: files_content_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_content_hash_index ON public.files USING btree (content_hash);


--
-- Name: files_mime_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX files_mime_index ON public.files USING btree (mime);


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
-- Name: fragments_source_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fragments_source_key_index ON public.fragments USING btree (source_key);


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
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: triggers_user_id_next_run_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX triggers_user_id_next_run_at_index ON public.triggers USING btree (user_id, next_run_at);


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

\unrestrict XInJbKuYc8u7fnLkCbhCZ0J7FQdSEy0ACl77oGVvIDOl5S4vTCejklHdMeO1HuB

--
-- PostgreSQL database dump
--

\restrict Yv9h8APkhHykxHq31pzMIg8mDHZClAlSMBAvuPHqONyGL6hlCPQwJ3LN3G3RwKA

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
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_04_14_000000_create_seer_logs_table	1
5	2025_04_15_031827_create_categories_table	1
6	2025_04_15_031854_add_category_id_to_seer_logs_table	1
7	2025_04_23_003504_add_source_to_fragments_table	1
8	2025_04_23_030918_addstate_to_fragments_table	1
9	2025_04_23_035658_add_metadata_to_fragments_table	1
10	2025_04_25_003334_add_vault_to_fragments_table	1
11	2025_04_25_055928_create_bookmarks_table	1
12	2025_08_23_234947_add_enhanced_fields_to_fragments_table	1
13	2025_08_23_235454_create_object_types_table	1
14	2025_08_23_235530_create_sources_table	1
15	2025_08_23_235600_create_fragment_links_table	1
16	2025_08_23_235627_create_fragment_tags_table	1
17	2025_08_23_235652_create_typed_object_tables	1
18	2025_08_23_235822_create_articles_and_article_fragments_tables	1
19	2025_08_23_235923_add_performance_indexes	1
20	2025_08_24_035414_add_last_viewed_at_to_bookmarks_table	1
21	2025_08_24_041146_create_chat_sessions_table	1
22	2025_08_24_191340_create_vaults_table	1
23	2025_08_24_191433_create_projects_table	1
24	2025_08_24_191511_add_project_id_to_fragments_table	1
25	2025_08_24_191525_add_vault_project_pinning_to_chat_sessions_table	1
26	2025_08_24_193015_assign_existing_chat_sessions_to_defaults	1
27	2025_08_24_193118_assign_existing_fragments_to_default_project	1
28	2025_08_24_235818_add_input_hash_to_fragments_table	1
29	2025_08_25_015605_add_metadata_columns_to_fragments_table	1
30	2025_08_25_023601_add_fulltext_to_fragments_table	1
31	2025_08_25_040630_create_recall_decisions_table	1
32	2025_08_25_040830_add_selection_stats_to_fragments_table	1
33	2025_08_29_002744_add_soft_deletes_to_chat_sessions_table	1
34	2025_08_29_014917_create_types_table	1
35	2025_08_29_015144_add_type_id_to_fragments_table	1
36	2025_08_29_015221_migrate_fragment_type_to_type_id	1
37	2025_08_30_045548_create_fragment_embeddings	2
38	2025_08_30_053234_add_model_hash_to_fragment_embeddings	3
39	2025_08_30_054011_add_model_hash_to_fragment_embeddings	3
43	2025_08_30_055905_add_edited_message_to_fragments_table	4
45	2025_08_31_184708_add_not_null_constraint_to_fragments_message	5
46	2025_09_01_022243_add_short_code_and_custom_name_to_chat_sessions_table	6
47	2025_09_01_022302_backfill_chat_session_short_codes	6
48	2025_09_14_000000_create_vault_routing_rules_table	7
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 48, true);


--
-- PostgreSQL database dump complete
--

\unrestrict Yv9h8APkhHykxHq31pzMIg8mDHZClAlSMBAvuPHqONyGL6hlCPQwJ3LN3G3RwKA

