from django.shortcuts import render

# Create your views here.
from django.http import HttpResponse
from django.views.generic import View

def index(request):
    return HttpResponse("Hello world!")

def jafv(request):
    return HttpResponse("this is a view based on a function")
    
class Jafc(View):
    obj='nothing'
    def get(self, request):
        return HttpResponse("value: %s" % self.obj)
