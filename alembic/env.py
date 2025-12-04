from logging.config import fileConfig
import sys
import os

# Add project root to path
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from sqlalchemy import engine_from_config
from sqlalchemy import pool

from alembic import context

# Import your models and config from backend
from backend.config import settings
from backend.database import Base
# Import all model classes to ensure they're registered with Base.metadata
from backend.models.user import User
from backend.models.article import Article
from backend.models.project import Project
from backend.models.about import About
from backend.models.skill import Skill
from backend.models.social_link import SocialLink
from backend.models.testimonial import Testimonial
from backend.models.seo_setting import SEOSetting
from backend.models.setting import Setting

# this is the Alembic Config object
config = context.config

# Override sqlalchemy.url with our settings
config.set_main_option('sqlalchemy.url', settings.database_url)

# Interpret the config file for Python logging.
if config.config_file_name is not None:
    fileConfig(config.config_file_name)

# add your model's MetaData object here for 'autogenerate' support
target_metadata = Base.metadata


def run_migrations_offline() -> None:
    """Run migrations in 'offline' mode."""
    url = config.get_main_option("sqlalchemy.url")
    context.configure(
        url=url,
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
    )

    with context.begin_transaction():
        context.run_migrations()


def run_migrations_online() -> None:
    """Run migrations in 'online' mode."""
    connectable = engine_from_config(
        config.get_section(config.config_ini_section),
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )

    with connectable.connect() as connection:
        context.configure(
            connection=connection, target_metadata=target_metadata
        )

        with context.begin_transaction():
            context.run_migrations()


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
