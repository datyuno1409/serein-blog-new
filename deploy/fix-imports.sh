#!/bin/bash
# Fix all imports in backend/api files

cd /var/www/serein-blog/backend/api

# Fix: from auth import → from ..auth import
sed -i 's/^from auth import/from ..auth import/g' *.py

# Fix: from schemas import → from ..schemas import  
sed -i 's/^from schemas import/from ..schemas import/g' *.py

# Fix: from models. → from ...models.
sed -i 's/^from models\./from ...models./g' *.py

echo "All imports fixed!"
ls -la *.py
