<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619160738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE addresses (id SERIAL NOT NULL, customer_id INT NOT NULL, type VARCHAR(20) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, street VARCHAR(255) NOT NULL, postal_code VARCHAR(10) NOT NULL, city VARCHAR(150) NOT NULL, state VARCHAR(150) DEFAULT NULL, country VARCHAR(150) DEFAULT NULL, is_default_billing BOOLEAN DEFAULT false NOT NULL, is_default_shipping BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6FCA75169395C3F3 ON addresses (customer_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "app_users" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, is_verified BOOLEAN DEFAULT false NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, is_system_account BOOLEAN DEFAULT false NOT NULL, agreed_to_terms_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, password_reset_token VARCHAR(255) DEFAULT NULL, password_reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, password_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, two_factor_authentication_secret VARCHAR(255) DEFAULT NULL, is_two_factor_authentication_enabled BOOLEAN DEFAULT false NOT NULL, failed_login_attempts INT DEFAULT 0 NOT NULL, last_failed_login_attempt_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, account_locked_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, apple_id VARCHAR(255) DEFAULT NULL, microsoft_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C2502824E7927C74 ON "app_users" (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C2502824F85E0677 ON "app_users" (username)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C250282476F5C865 ON "app_users" (google_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C2502824571323F9 ON "app_users" (apple_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C250282444F23B3E ON "app_users" (microsoft_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".agreed_to_terms_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".password_reset_token_expires_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".password_requested_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".last_failed_login_attempt_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".account_locked_until IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "app_users".last_login_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE brands (id SERIAL NOT NULL, logo_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, short_description TEXT DEFAULT NULL, long_description TEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, is_active BOOLEAN DEFAULT true NOT NULL, is_featured BOOLEAN DEFAULT false NOT NULL, meta_title TEXT DEFAULT NULL, meta_description TEXT DEFAULT NULL, keywords VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_7EA244345E237E06 ON brands (name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_7EA24434989D9B62 ON brands (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7EA24434F98F144A ON brands (logo_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id SERIAL NOT NULL, tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, meta_title TEXT DEFAULT NULL, meta_description TEXT DEFAULT NULL, description TEXT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, lvl INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_64C19C19F75D7B0 ON category (external_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_64C19C1A977936C ON category (tree_root)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE customers (id SERIAL NOT NULL, user_account_id INT NOT NULL, type VARCHAR(50) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, company_form VARCHAR(100) DEFAULT NULL, siret VARCHAR(100) DEFAULT NULL, vat_number VARCHAR(50) DEFAULT NULL, phone_number VARCHAR(30) DEFAULT NULL, is_verified BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_62534E2126E94372 ON customers (siret)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_62534E213C0C9956 ON customers (user_account_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE image_assets (id SERIAL NOT NULL, product_id INT DEFAULT NULL, product_variant_id INT DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, alt_text VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, original_filename VARCHAR(255) DEFAULT NULL, filesize INT DEFAULT NULL, mime_type VARCHAR(50) DEFAULT NULL, width SMALLINT DEFAULT NULL, height SMALLINT DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, is_primary BOOLEAN DEFAULT false NOT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D6CC23469F75D7B0 ON image_assets (external_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D6CC23464584665A ON image_assets (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D6CC2346A80EF684 ON image_assets (product_variant_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product (id SERIAL NOT NULL, category_id INT NOT NULL, brand_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, short_description TEXT DEFAULT NULL, long_description TEXT DEFAULT NULL, meta_title TEXT DEFAULT NULL, meta_description TEXT DEFAULT NULL, keywords VARCHAR(255) DEFAULT NULL, vat_rate INT DEFAULT 2000 NOT NULL, manufacturer_code VARCHAR(255) DEFAULT NULL, condition VARCHAR(50) DEFAULT 'new' NOT NULL, is_visible BOOLEAN DEFAULT true NOT NULL, is_featured BOOLEAN DEFAULT false NOT NULL, base_reference VARCHAR(255) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D34A04AD1D8FD89E ON product (base_reference)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD44F5D008 ON product (brand_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_extra_categories (product_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(product_id, category_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4059BB194584665A ON product_extra_categories (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4059BB1912469DE2 ON product_extra_categories (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_related_products (product_source_id INT NOT NULL, product_target_id INT NOT NULL, PRIMARY KEY(product_source_id, product_target_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9BB5700BFB9CAAEC ON product_related_products (product_source_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9BB5700B7B2EBDEB ON product_related_products (product_target_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_variants (id SERIAL NOT NULL, product_id INT NOT NULL, sku VARCHAR(255) DEFAULT NULL, gtin VARCHAR(255) DEFAULT NULL, price_excl_tax_and_pre_eco_tax INT DEFAULT 0 NOT NULL, sale_price_excl_tax_and_pre_eco_tax INT DEFAULT NULL, is_on_sale BOOLEAN DEFAULT false NOT NULL, eco_tax_ht INT DEFAULT 0 NOT NULL, stock INT DEFAULT 0 NOT NULL, availability_status VARCHAR(50) DEFAULT 'On Order' NOT NULL, is_preorder BOOLEAN DEFAULT false NOT NULL, replenishment_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, width DOUBLE PRECISION DEFAULT NULL, height DOUBLE PRECISION DEFAULT NULL, depth DOUBLE PRECISION DEFAULT NULL, external_id_variant VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_78283976F9038C4 ON product_variants (sku)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_782839764584665A ON product_variants (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_variant_options (product_variant_id INT NOT NULL, variant_option_value_id INT NOT NULL, PRIMARY KEY(product_variant_id, variant_option_value_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D1BD25CA80EF684 ON product_variant_options (product_variant_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D1BD25CCA7C39F1 ON product_variant_options (variant_option_value_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (id SERIAL NOT NULL, user_id INT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9BACE7E1A76ED395 ON refresh_tokens (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE variant_option_types (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, code VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D8E606B15E237E06 ON variant_option_types (name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D8E606B1989D9B62 ON variant_option_types (slug)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D8E606B177153098 ON variant_option_types (code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE variant_option_values (id SERIAL NOT NULL, option_type_id INT NOT NULL, value VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, color_code VARCHAR(7) DEFAULT NULL, code VARCHAR(100) DEFAULT NULL, is_visible BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_A09929DF77153098 ON variant_option_values (code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A09929DFDDB89BE6 ON variant_option_values (option_type_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vat_rates (country_code VARCHAR(2) NOT NULL, rate INT NOT NULL, PRIMARY KEY(country_code))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE addresses ADD CONSTRAINT FK_6FCA75169395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE brands ADD CONSTRAINT FK_7EA24434F98F144A FOREIGN KEY (logo_id) REFERENCES image_assets (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD CONSTRAINT FK_64C19C1A977936C FOREIGN KEY (tree_root) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE customers ADD CONSTRAINT FK_62534E213C0C9956 FOREIGN KEY (user_account_id) REFERENCES "app_users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE image_assets ADD CONSTRAINT FK_D6CC23464584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE image_assets ADD CONSTRAINT FK_D6CC2346A80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variants (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_extra_categories ADD CONSTRAINT FK_4059BB194584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_extra_categories ADD CONSTRAINT FK_4059BB1912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_related_products ADD CONSTRAINT FK_9BB5700BFB9CAAEC FOREIGN KEY (product_source_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_related_products ADD CONSTRAINT FK_9BB5700B7B2EBDEB FOREIGN KEY (product_target_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variant_options ADD CONSTRAINT FK_8D1BD25CA80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variants (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variant_options ADD CONSTRAINT FK_8D1BD25CCA7C39F1 FOREIGN KEY (variant_option_value_id) REFERENCES variant_option_values (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES "app_users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE variant_option_values ADD CONSTRAINT FK_A09929DFDDB89BE6 FOREIGN KEY (option_type_id) REFERENCES variant_option_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE addresses DROP CONSTRAINT FK_6FCA75169395C3F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE brands DROP CONSTRAINT FK_7EA24434F98F144A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP CONSTRAINT FK_64C19C1A977936C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE customers DROP CONSTRAINT FK_62534E213C0C9956
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE image_assets DROP CONSTRAINT FK_D6CC23464584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE image_assets DROP CONSTRAINT FK_D6CC2346A80EF684
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04AD12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04AD44F5D008
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_extra_categories DROP CONSTRAINT FK_4059BB194584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_extra_categories DROP CONSTRAINT FK_4059BB1912469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_related_products DROP CONSTRAINT FK_9BB5700BFB9CAAEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_related_products DROP CONSTRAINT FK_9BB5700B7B2EBDEB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variants DROP CONSTRAINT FK_782839764584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variant_options DROP CONSTRAINT FK_8D1BD25CA80EF684
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_variant_options DROP CONSTRAINT FK_8D1BD25CCA7C39F1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE refresh_tokens DROP CONSTRAINT FK_9BACE7E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE variant_option_values DROP CONSTRAINT FK_A09929DFDDB89BE6
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE addresses
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "app_users"
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE brands
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE customers
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE image_assets
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_extra_categories
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_related_products
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_variants
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_variant_options
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE variant_option_types
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE variant_option_values
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vat_rates
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
