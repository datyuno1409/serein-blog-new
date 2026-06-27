from pathlib import Path
import shutil
import sys

from jinja2 import Environment, FileSystemLoader, select_autoescape

ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"

sys.path.insert(0, str(ROOT))

from backend.roadmap_data import CAREER_LEVELS, ROADMAP_QUOTE, ROADMAP_TOPICS


def write_text(path: Path, content: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")


def render_template(env: Environment, template_name: str, output_path: str, **context) -> None:
    template = env.get_template(template_name)
    write_text(DIST / output_path, template.render(current_user=None, **context))


def main() -> None:
    if DIST.exists():
        shutil.rmtree(DIST)
    DIST.mkdir(parents=True)

    shutil.copytree(ROOT / "frontend" / "assets", DIST / "assets")

    env = Environment(
        loader=FileSystemLoader([ROOT / "frontend" / "templates", ROOT]),
        autoescape=select_autoescape(["html", "xml"]),
    )

    render_template(env, "index.html", "index.html", nav_active="home")
    render_template(env, "blog.html", "blog/index.html", nav_active="blog")
    render_template(
        env,
        "roadmap_page.html",
        "roadmap/index.html",
        nav_active="roadmap",
        career_levels=CAREER_LEVELS,
        roadmap_topics=ROADMAP_TOPICS,
        roadmap_quote=ROADMAP_QUOTE,
    )
    render_template(env, "learning/login.html", "login/index.html", nav_active="login")
    render_template(env, "learning/register.html", "register/index.html", nav_active="register")

    shutil.copy2(ROOT / "frontend" / "templates" / "404.html", DIST / "404.html")
    write_text(
        DIST / "_redirects",
        "\n".join(
            [
                "/home / 301",
                "/portfolio / 301",
                "/about / 301",
                "/post /blog 302",
                "/learning /login 302",
                "/learning/* /login 302",
                "/my-profile /login 302",
                "/my-course /blog 302",
                "/* /404.html 404",
                "",
            ]
        ),
    )


if __name__ == "__main__":
    main()
