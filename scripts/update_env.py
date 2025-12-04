"""
Script to update .env file with PostgreSQL configuration
"""
import os

def update_env_file():
    """Update .env file with PostgreSQL connection string"""
    env_path = ".env"
    
    # Read .env.example as template if .env doesn't exist
    if not os.path.exists(env_path):
        with open(".env.example", "r", encoding="utf-8") as f:
            content = f.read()
    else:
        with open(env_path, "r", encoding="utf-8") as f:
            content = f.read()
    
    # Update DATABASE_URL
    lines = content.split("\n")
    updated_lines = []
    
    for line in lines:
        if line.startswith("DATABASE_URL=sqlite"):
            # Comment out SQLite
            updated_lines.append(f"# {line}")
        elif line.startswith("# DATABASE_URL=postgresql"):
            # Uncomment and update PostgreSQL URL
            updated_lines.append("DATABASE_URL=postgresql://postgres:Fpt1409!@@localhost:5432/serein_db")
        else:
            updated_lines.append(line)
    
    # Write back to .env
    with open(env_path, "w", encoding="utf-8") as f:
        f.write("\n".join(updated_lines))
    
    print("✓ Updated .env file with PostgreSQL configuration")

if __name__ == "__main__":
    update_env_file()
