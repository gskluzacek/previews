from __future__ import unicode_literals

from django.db import models
from django.utils import timezone

# #################################
#         REGARDING TIME
# #################################
#
# create a max date-time object of 12/31/9999 00:00:00 in UTC
# use this value to indicate has not been superseded
# 
# >>> from datetime import datetime
# >>> import pytz
# >>> mdt = datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc)
# >>> fmt = '%Y-%m-%d %H:%M:%S %Z%z'
# >>> print mdt.strftime(fmt)
# 9999-12-31 00:00:00 UTC+0000


class Publisher(models.Model):
    seq = models.PositiveIntegerField()
    pub_key = models.BigIntegerField()
    name = models.CharField(max_length=60)
    updt_reason = models.CharField(max_length=200)
    status = models.CharField(max_length=15)    # used to indicate a key is no longer a going concern (i.e., a publisher went out of business, a series has been cancled, etc. )
    valid_from = models.DateField()     # date that the record is effective, can be a past or futrue date
    valid_to = models.DateField()       # date that the record is expired, can be a past or future date, but must be greater than valid_from date
    trans_rcdd = models.DateTimeField(default=timezone.now)     # date-time the transaction was recorded, `in practice`, this should be the current date-time, so it cannot be in the future. but obviously, it can be less than the current date-time for transacitons recorded in the past
    trans_spsd = models.DateTimeField()                         # date-time the transaction was superseded (i.e., `updated` data was inserted, replacing the previous data), must be greater than the trans_rcdd date-time
    
    class Meta:
        unique_together = (("pub_key","valid_from","trans_rcdd"),)

class Series(models.Model):
    seq = models.PositiveIntegerField()
    ser_key = models.BigIntegerField()
    pub_key = models.BigIntegerField()
    title = models.CharField(max_length=60)
    year = models.PositiveSmallIntegerField()
    updt_reason = models.CharField(max_length=200)
    status = models.CharField(max_length=15)
    valid_from = models.DateField()
    valid_to = models.DateField()
    trans_rcdd = models.DateTimeField(default=timezone.now)
    trans_spsd = models.DateTimeField()
    
    class Meta:
        unique_together = (("pub_key","ser_key","valid_from","trans_rcdd"),)

class Issue(models.Model):
    seq = models.PositiveIntegerField()
    iss_key = models.BigIntegerField()
    ser_key = models.BigIntegerField()
    type = models.CharField(max_length=10)
    iss_num = models.PositiveSmallIntegerField()
    cover_dt = models.DateField()
    updt_reason = models.CharField(max_length=200)
    trans_rcdd = models.DateTimeField(default=timezone.now)
    trans_spsd = models.DateTimeField()
    
    class Meta:
        unique_together = (("ser_key","iss_key","trans_rcdd"),)

class Variant (models.Model):
    seq = models.PositiveIntegerField()
    var_key = models.BigIntegerField()
    iss_key = models.BigIntegerField()
    type = models.CharField(max_length=10)
    sub_type = models.CharField(max_length=10)
    cover_dt = models.DateField()
    updt_reason = models.CharField(max_length=200)
    trans_rcdd = models.DateTimeField(default=timezone.now)
    trans_spsd = models.DateTimeField()
    
    class Meta:
        unique_together = (("iss_key","var_key","trans_rcdd"),)

class CoverDtOvrrd (models.Model):
    seq = models.PositiveIntegerField()
    ser_key = models.BigIntegerField()
    ovrrd_start = models.DateField()
    ovrrd_end = models.DateField()
    ovrrd_cover_dt = models.DateField()
    trans_rcdd = models.DateTimeField(default=timezone.now)
    trans_spsd = models.DateTimeField()
    
    class Meta:
        unique_together = (("ser_key","ovrrd_start","ovrrd_cover_dt","trans_rcdd"),)

class Keys(models.Model):
    table_nm = models.CharField(max_length=200, unique=True)
    key_value = models.BigIntegerField(default=1)

class Sequences(models.Model):
    table_nm = models.CharField(max_length=200)
    key_value = models.BigIntegerField()
    seq_value = models.PositiveIntegerField(default=1)
    
    class Meta:
        unique_together = (("table_nm","key_value"),)
