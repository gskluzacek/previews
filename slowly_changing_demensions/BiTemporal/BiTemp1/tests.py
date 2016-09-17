from django.test import TestCase

from BiTemp1 import temporal as bt
from BiTemp1.models import Publisher, Series, Issue, Variant, CoverDtOvrrd, Keys, Sequences

some_variable = 1

class TestTest(TestCase):
    def setUp(self):
        global some_variable
        print "self test"
        some_variable = 2
        
        
        
    def test_i_can_run_a_test(self):
        global some_variable
        """this is just a test test"""
        print "calling ==> test_i_can_run_a_test"
        self.assertEqual(some_variable, 1)
        
    def test_i_can_fail_a_test(self):
        global some_variable
        """this is just a test test"""
        print "calling => test_i_can_fail_a_test"
        self.assertEqual(some_variable, 2)
