from django.conf.urls import url

from . import views

urlpatterns = [
    url(r'^$', views.index, name='index'),
    url(r'^func/$', views.jafv, name='testfunc1'),
    url(r'^class/$', views.Jafc.as_view(obj='i like turtles'), name='testclass1'),
]
