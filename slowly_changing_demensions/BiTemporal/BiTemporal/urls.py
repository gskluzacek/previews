#BiTemporal URL Configuration
from django.conf.urls import include, url
from django.contrib import admin

urlpatterns = [
    url(r'^admin/', admin.site.urls),
    url(r'^BiTemp1/', include('BiTemp1.urls')),
]
