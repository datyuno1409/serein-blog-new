"""Add learning platform tables

Revision ID: 9f4d9b2c7a11
Revises: 3d1ee84e2369
Create Date: 2026-06-23 22:55:00
"""
from alembic import op
import sqlalchemy as sa


revision = '9f4d9b2c7a11'
down_revision = '3d1ee84e2369'
branch_labels = None
depends_on = None


def upgrade() -> None:
    with op.batch_alter_table('users', schema=None) as batch_op:
        batch_op.add_column(sa.Column('email', sa.String(length=255), nullable=True))
        batch_op.add_column(sa.Column('full_name', sa.String(length=120), nullable=True))
        batch_op.add_column(sa.Column('bio', sa.Text(), nullable=True))
        batch_op.add_column(sa.Column('learning_goal', sa.String(length=255), nullable=True))
        batch_op.add_column(sa.Column('avatar_url', sa.String(length=255), nullable=True))
        batch_op.add_column(sa.Column('weekly_hours', sa.Integer(), nullable=True))
        batch_op.create_index(batch_op.f('ix_users_email'), ['email'], unique=True)

    op.execute("UPDATE users SET email = username || '@local.dev' WHERE email IS NULL")
    op.execute("UPDATE users SET full_name = username WHERE full_name IS NULL")
    op.execute("UPDATE users SET weekly_hours = 1 WHERE weekly_hours IS NULL")

    with op.batch_alter_table('users', schema=None) as batch_op:
        batch_op.alter_column('email', existing_type=sa.String(length=255), nullable=False)
        batch_op.alter_column('full_name', existing_type=sa.String(length=120), nullable=False)
        batch_op.alter_column('weekly_hours', existing_type=sa.Integer(), nullable=False)

    op.create_table('learning_paths',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=False),
        sa.Column('title', sa.String(length=255), nullable=False),
        sa.Column('target_role', sa.String(length=120), nullable=False),
        sa.Column('focus_area', sa.String(length=120), nullable=False),
        sa.Column('pace', sa.String(length=80), nullable=False),
        sa.Column('weekly_hours', sa.Integer(), nullable=False),
        sa.Column('description', sa.Text(), nullable=True),
        sa.Column('active', sa.Boolean(), nullable=False, server_default=sa.text('1')),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.Column('updated_at', sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(['user_id'], ['users.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_learning_paths_id'), 'learning_paths', ['id'], unique=False)
    op.create_index(op.f('ix_learning_paths_user_id'), 'learning_paths', ['user_id'], unique=False)

    op.create_table('courses',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('learning_path_id', sa.Integer(), nullable=False),
        sa.Column('title', sa.String(length=255), nullable=False),
        sa.Column('slug', sa.String(length=255), nullable=False),
        sa.Column('category', sa.String(length=80), nullable=False),
        sa.Column('language', sa.String(length=80), nullable=False),
        sa.Column('level', sa.String(length=80), nullable=False),
        sa.Column('summary', sa.Text(), nullable=False),
        sa.Column('estimated_hours', sa.Integer(), nullable=False),
        sa.Column('tags', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.Column('updated_at', sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(['learning_path_id'], ['learning_paths.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_courses_id'), 'courses', ['id'], unique=False)
    op.create_index(op.f('ix_courses_learning_path_id'), 'courses', ['learning_path_id'], unique=False)
    op.create_index(op.f('ix_courses_slug'), 'courses', ['slug'], unique=True)

    op.create_table('lessons',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('course_id', sa.Integer(), nullable=False),
        sa.Column('title', sa.String(length=255), nullable=False),
        sa.Column('lesson_type', sa.String(length=80), nullable=False),
        sa.Column('content', sa.Text(), nullable=False),
        sa.Column('order_index', sa.Integer(), nullable=False),
        sa.Column('duration_minutes', sa.Integer(), nullable=False),
        sa.Column('xp_reward', sa.Integer(), nullable=False),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.Column('updated_at', sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(['course_id'], ['courses.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_lessons_id'), 'lessons', ['id'], unique=False)
    op.create_index(op.f('ix_lessons_course_id'), 'lessons', ['course_id'], unique=False)

    op.create_table('flashcards',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('lesson_id', sa.Integer(), nullable=False),
        sa.Column('front_text', sa.Text(), nullable=False),
        sa.Column('back_text', sa.Text(), nullable=False),
        sa.Column('pronunciation', sa.String(length=255), nullable=True),
        sa.Column('example', sa.Text(), nullable=True),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.ForeignKeyConstraint(['lesson_id'], ['lessons.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_flashcards_id'), 'flashcards', ['id'], unique=False)
    op.create_index(op.f('ix_flashcards_lesson_id'), 'flashcards', ['lesson_id'], unique=False)

    op.create_table('user_course_progress',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=False),
        sa.Column('course_id', sa.Integer(), nullable=False),
        sa.Column('completed_lessons', sa.Integer(), nullable=False),
        sa.Column('total_lessons', sa.Integer(), nullable=False),
        sa.Column('completion_rate', sa.Integer(), nullable=False),
        sa.Column('current_streak', sa.Integer(), nullable=False),
        sa.Column('last_activity', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.Column('preferences', sa.JSON(), nullable=True),
        sa.ForeignKeyConstraint(['course_id'], ['courses.id']),
        sa.ForeignKeyConstraint(['user_id'], ['users.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_user_course_progress_id'), 'user_course_progress', ['id'], unique=False)
    op.create_index(op.f('ix_user_course_progress_user_id'), 'user_course_progress', ['user_id'], unique=False)
    op.create_index(op.f('ix_user_course_progress_course_id'), 'user_course_progress', ['course_id'], unique=False)

    op.create_table('user_lesson_progress',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=False),
        sa.Column('lesson_id', sa.Integer(), nullable=False),
        sa.Column('status', sa.String(length=40), nullable=False),
        sa.Column('notes', sa.Text(), nullable=True),
        sa.Column('writing_submission', sa.Text(), nullable=True),
        sa.Column('score', sa.Integer(), nullable=False),
        sa.Column('updated_at', sa.DateTime(timezone=True), server_default=sa.text('(CURRENT_TIMESTAMP)'), nullable=True),
        sa.ForeignKeyConstraint(['lesson_id'], ['lessons.id']),
        sa.ForeignKeyConstraint(['user_id'], ['users.id']),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_user_lesson_progress_id'), 'user_lesson_progress', ['id'], unique=False)
    op.create_index(op.f('ix_user_lesson_progress_user_id'), 'user_lesson_progress', ['user_id'], unique=False)
    op.create_index(op.f('ix_user_lesson_progress_lesson_id'), 'user_lesson_progress', ['lesson_id'], unique=False)


def downgrade() -> None:
    op.drop_index(op.f('ix_user_lesson_progress_lesson_id'), table_name='user_lesson_progress')
    op.drop_index(op.f('ix_user_lesson_progress_user_id'), table_name='user_lesson_progress')
    op.drop_index(op.f('ix_user_lesson_progress_id'), table_name='user_lesson_progress')
    op.drop_table('user_lesson_progress')

    op.drop_index(op.f('ix_user_course_progress_course_id'), table_name='user_course_progress')
    op.drop_index(op.f('ix_user_course_progress_user_id'), table_name='user_course_progress')
    op.drop_index(op.f('ix_user_course_progress_id'), table_name='user_course_progress')
    op.drop_table('user_course_progress')

    op.drop_index(op.f('ix_flashcards_lesson_id'), table_name='flashcards')
    op.drop_index(op.f('ix_flashcards_id'), table_name='flashcards')
    op.drop_table('flashcards')

    op.drop_index(op.f('ix_lessons_course_id'), table_name='lessons')
    op.drop_index(op.f('ix_lessons_id'), table_name='lessons')
    op.drop_table('lessons')

    op.drop_index(op.f('ix_courses_slug'), table_name='courses')
    op.drop_index(op.f('ix_courses_learning_path_id'), table_name='courses')
    op.drop_index(op.f('ix_courses_id'), table_name='courses')
    op.drop_table('courses')

    op.drop_index(op.f('ix_learning_paths_user_id'), table_name='learning_paths')
    op.drop_index(op.f('ix_learning_paths_id'), table_name='learning_paths')
    op.drop_table('learning_paths')

    with op.batch_alter_table('users', schema=None) as batch_op:
        batch_op.drop_index(batch_op.f('ix_users_email'))
        batch_op.drop_column('weekly_hours')
        batch_op.drop_column('avatar_url')
        batch_op.drop_column('learning_goal')
        batch_op.drop_column('bio')
        batch_op.drop_column('full_name')
        batch_op.drop_column('email')
