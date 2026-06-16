-- AnyVar 1.0.0's Postgres storage GiST-indexes a `character varying` column
-- (ix_location_ref_overlap on locations.sequence_reference_id). Stock PostgreSQL +
-- btree_gist only registers a DEFAULT gist operator class for `text` and `bpchar`,
-- NOT for `varchar`, so anyvar's schema creation fails at startup with:
--   "data type character varying has no default operator class for access method gist".
-- We install btree_gist and register a DEFAULT varchar gist opclass that reuses the
-- btree_gist text support functions. Runs once, on first init of the anyvar storage db.
CREATE EXTENSION IF NOT EXISTS btree_gist;

-- Operators are spelled with explicit (text, text) types: there are no
-- varchar-specific comparison operators, so a bare `<` fails to resolve for varchar.
-- varchar is binary-coercible to text, so the text operators + btree_gist text support
-- functions serve a varchar GiST index correctly. STORAGE gbtreekey_var matches the
-- btree_gist text opclass storage type.
DO $$
BEGIN
    CREATE OPERATOR CLASS gist_varchar_ops DEFAULT FOR TYPE varchar USING gist AS
        OPERATOR 1 < (text, text),
        OPERATOR 2 <= (text, text),
        OPERATOR 3 = (text, text),
        OPERATOR 4 >= (text, text),
        OPERATOR 5 > (text, text),
        FUNCTION 1 gbt_text_consistent(internal, text, int2, oid, internal),
        FUNCTION 2 gbt_text_union(internal, internal),
        FUNCTION 3 gbt_text_compress(internal),
        FUNCTION 4 gbt_decompress(internal),
        FUNCTION 5 gbt_text_penalty(internal, internal, internal),
        FUNCTION 6 gbt_text_picksplit(internal, internal),
        FUNCTION 7 gbt_text_same(gbtreekey_var, gbtreekey_var, internal),
        STORAGE gbtreekey_var;
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;
