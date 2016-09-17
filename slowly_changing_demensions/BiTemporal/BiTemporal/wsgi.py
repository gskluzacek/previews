"""
WSGI config for BiTemporal project.

It exposes the WSGI callable as a module-level variable named ``application``.

For more information on this file, see
https://docs.djangoproject.com/en/1.9/howto/deployment/wsgi/
"""

import os, sys
sys.path.append('C:/Users/sklugr02/Bitnami Django Stack projects/BiTemporal')
os.environ.setdefault("PYTHON_EGG_CACHE", "C:/Users/sklugr02/Bitnami Django Stack projects/BiTemporal/egg_cache")

from django.core.wsgi import get_wsgi_application

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "BiTemporal.settings")

application = get_wsgi_application()
