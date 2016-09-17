import pytz
from datetime import date, datetime
from django.utils import timezone
from django.db import connection, transaction
from BiTemp1.models import Publisher, Series, Issue, Variant, CoverDtOvrrd, Keys, Sequences

def test():
    print "hello world..."

    
def create_pub(s, k, n, vf, vt, tr, ts):
    p = Publisher(
        seq = s,
        pub_key = k,
        name = n,
        updt_reason = "TESTING...",
        status = "ACTIVE",
        valid_from = vf,
        valid_to   = vt,
        trans_spsd = ts
    )
    p.save()
    return p

def cleanup_pub(k):
    Publisher.objects.filter(pub_key=k).delete()

def cleanup_pubs(ks):
    Publisher.objects.filter(pub_key__in=ks).delete()
    

def first_pub(k, n, vf='2001-01-01'):
    return create_pub(1, k, n, vf, '9999-12-31', None, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))

def first_pub_exp(k, n, vf='2001-01-01', vt='2005-12-31'):
    return create_pub(1, k, n, vf, vt, None, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
    
def get_pub_corr_curr_seq_obj(p):
    return get_pub_corr_curr_seq(p.pub_key, p.seq)

def get_pub_corr_curr_seq(pub_key, seq):
    return Publisher.objects.get(pub_key=pub_key, seq=seq, trans_spsd=datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))

def refresh_pub_obj(p):
    return refresh_pub(p.id)
    
def refresh_pub(id):
    return Publisher.objects.get(id=id)
    
def is_date_valid(dt):
    if dt == None:
        return False
        
    try:
        datetime.strptime(dt, '%Y-%m-%d')
        return True
    except ValueError:
        return False
    
def check_pub_name(pub_name, pub_key=None):
    if pub_key == None:
        flag = Publisher.objects.filter(name=pub_name).exists()
    else:
        flag = Publisher.objects.filter(name=pub_name).exclude(pub_key=pub_key).exists()
    return flag

#
# this function takes a table name as its argument
# and returns the next primary-key value for the table
#

# The caller of this function must start a transaction
# a record lock will be aquired and only released when
# the transaction is commited/rolled back    
def get_key(table):
    try:
        key_obj = Keys.objects.select_for_update().get(table_nm=table)
        key_obj.key_value += 1
        key_obj.save()
        key = key_obj.key_value
    except Keys.DoesNotExist:
        Keys.objects.create(table_nm=table)
        key = 1
        
    return key

# The caller of this function must start a transaction
# a record lock will be aquired and only released when
# the transaction is commited/rolled back    
def get_seq(table, key):
    # TODO: may be add logic that checks that the key is less than or equal to the value on the KEYS table
    #       or somehow validate on the table that uses the KEY value?
    try:
        seq_obj = Sequences.objects.select_for_update().get(table_nm=table,key_value=key)
        seq_obj.seq_value += 1
        seq_obj.save()
        seq = seq_obj.seq_value
    except Sequences.DoesNotExist:
        Sequences.objects.create(table_nm=table,key_value=key)
        seq = 1
        
    return seq

# Use Case #1
#
# Insert first record for a given PUB_KEY
#   with VALID_TO date not set
#  
# - SEQ should be 1
# - VALID_FROM should not be null and should not be 9999-12-31
# - VALID_TO should be not set (i.e., equal to 9999-12-31)
# - TRANS_RCDD should be set to current date-time UTC
# - TRANS_SPSD should be not be set (i.e., be equal to 9999-12-31 00:00:00 and be UTC)
#
# - after creating, the publisher NAME should only exist for one pub_key
#
# 
# This function will create its own transaction and either commit or rollback when it exits
# 
def create_pub_key(pub_name, valid_from_date):
    # start transaction
    transaction.set_autocommit(False)
    
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    pub_name = pub_name.strip()
    if pub_name == None or len(pub_name) == 0:
        print "error: pub_name is required" % (pub_name,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    try:
        if check_pub_name(pub_name):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
    
        pub_key = get_key('publisher')
        seq = get_seq('publisher', pub_key)
        
        if seq != 1:
            print "error: sequnce should be 1 for a new key, got seq of: %d for new pub_key: %d" % (seq, pub_key)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, '9999-12-31', None, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
    
        transaction.commit()
    except:
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)

    return pub


# Use Case #2
#
# Insert first record for a given PUB_KEY
#   with VALID_TO date set
#  
# - SEQ should be 1
# - VALID_FROM should not be null and should not be 9999-12-31
# - VALID_TO should not be null and should not be 9999-12-31
# - VALID_TO should be greater than VALID_FROM
# - TRANS_RCDD should be set to current date-time UTC
# - TRANS_SPSD should be not be set (i.e., be equal to 9999-12-31 00:00:00 and be UTC)
#
# - after creating, the publisher NAME should only exist for one pub_key
#
# 
# This function will create its own transaction and either commit or rollback when it exits
# 
def create_pub_key_expired(pub_name, valid_from_date, valid_to_date):
    # start transaction
    transaction.set_autocommit(False)
    
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        return None
        
    if not is_date_valid(valid_to_date) or valid_to_date == '9999-12-31':
        print "error: invalid valid_to date: %s" % (valid_to_date,)
        return None
        
    dt_fmt = '%Y-%m-%d'
    if datetime.strptime(valid_to_date, dt_fmt).date() <= datetime.strptime(valid_from_date, dt_fmt).date():
        print "error: valid_to: %s, must be greater than valid_from: %s" % (valid_to_date, valid_from_date)
        return None
        
    pub_name = pub_name.strip()
    if pub_name == None or len(pub_name) == 0:
        print "error: pub_name is required" % (pub_name,)
        return None
        
    try:
        if check_pub_name(pub_name):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            return None
    
        pub_key = get_key('publisher')
        seq = get_seq('publisher', pub_key)
        
        if seq != 1:
            print "error: sequnce should be 1 for a new key, got seq of: %d for new pub_key: %d" % (seq, pub_key)
            return None
            
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, valid_to_date, None, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
    
        transaction.commit()
    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        raise
    finally:
        print "resetting auto commit"
        transaction.set_autocommit(True)

    return pub

    
# USE CASE NOTES

# CONCEPT: the last record must always have a VALID_TO date of 9999-12-31
# (user cannot set VALID_TO to 9999-12-31, although it may be posible to clear the value from VALID_TO, and in which case, the system would set VALID_TO to 9999-12-31)
# if adding a new entry after the last entry (i.e., an existing entry with a VALID_TO value of 9999-12-31) user cannot set the new VALID_TO, the system will set it to 9999-12-31
# (additionally the VALID_TO of the previous (old) record will be set to the VALID_FROM of the new record)
# if creating a new key (i.e., entering the first entry for a key), the user cannot set the VALID_TO, the system will set it to 9999-12-31

# CONCEPT: key status
# a field that indicates if a key is able to participate in transactions with a transaction date between the VALID_FROM and the VALID_TO (inclusive)
# this could be used to indicate a `blackout period` for a given key, where transactions can be logged against the key before the VALID_FROM and after the VALID_TO, but not durring the period of VALID_FROM and VALID_TO (inclusive)
# this most normally would be used to indicate that a key has been terminated: i.e., a publisher is no longer a 'going concern', a series has ended/been cancleed

# CONCEPT: Record Delete indicator
# should we have one?

# CONCEPT: Attribute Uniqueness
# for attributes that are suposed to be unqiue for a given table, should uniqueness only consider if the attribute value already exists with a different key
# or should uniqueness consider the valid_from & valid_to and/or trans_spsd fields? That is, 2 different keys can have the same attribute value if they have
# non-overlaping valid_from & valid_to dates. Or if the trans_spsd date is set for one or both of the records?

# CONCEPT: Stale Data
# the data 1 user is viewing could become stale if a second user updates the same data
# this would be a problem if the first person attempted to update the data (without refreshing) after the second person (successfully) updated the data
#
# need to implement checks to detect stale data
# will do this when I refactor the code base


# CONCEPT: Version / Revision navigation
# use of multiple doubly linked list to navigate
#  - between Versions
#  - between Revisions of a (single) Version
#  - between the most current Revision of one Version to the oldest Revision of the next Version
#    or vice versa (oldest revision of one version to the current revision of the previous version)




# Use Case #3
#
# Insert add an additional record for a given PUB_KEY at the end (i.e., after all other records for the key)
#   with VALID_TO date not set
#  
# -- the ID of the PUBLISHER to be modified should be passed in
# -- the ID passed in must exist on the table
# -- the new publisher NAME (should be passed in) should not exist for another pub_key and should not be null
# = the new SEQ should be the max SEQ + 1 for the key
# -- the new VALID_FROM should not be null, should not be 9999-12-31 and should be greater than the VALID_FROM for the old record
# = the new VALID_TO should be not set (i.e., equal to 9999-12-31)
# = the new TRANS_RCDD should be set to current date-time UTC
# = the new TRANS_SPSD should be not be set (i.e., be equal to 9999-12-31 00:00:00 and be UTC)
# == the old TRANS_SPSD should be set to current date-time UTC
# == the record for the old SEQ should be copied, setting
#   - the copied VALID_TO to the new record's VALID_FROM
#   - the copied TRANS_RCDD should be set to current date-time UTC
#   - the new TRANS_SPSD should be not be set (i.e., be equal to 9999-12-31 00:00:00 and be UTC)
#  
# 
# This function will create its own transaction and either commit or rollback when it exits
# 
def append_pub_key(pub_key, pub_name, valid_from_date):
    # start transaction
    transaction.set_autocommit(False)
    
    # validate the VALID_FROM
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    pub_name = pub_name.strip()
    if pub_name == None or len(pub_name) == 0:
        print "error: pub_name is required" % (pub_name,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    curr_dt_tm = datetime.now(pytz.utc)
    
    try:
        if check_pub_name(pub_name, pub_key):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # get the `last` record for the existing KEY
        old_pub = Publisher.objects.filter(pub_key=pub_key, trans_spsd=datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc)).order_by('-valid_from').first()
        
        if old_pub == None:
            print "error: specified pub_key: %d, does not exist. Rolling back transaction." % (pub_key,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None

        dt_fmt = '%Y-%m-%d'
        if datetime.strptime(valid_from_date, dt_fmt).date() <= old_pub.valid_from:
            print "error: valid_from: %s, must be greater than old valid_from: %s" % (valid_from_date, old_pub.valid_from)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        old_pub.trans_spsd = curr_dt_tm
        old_pub.save()
        
        cpy_pub = create_pub(old_pub.seq, pub_key, old_pub.name, old_pub.valid_from, valid_from_date, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        seq = get_seq('publisher', pub_key)
        
        if seq == 1:
            print "error: got %d for sequence value. sequnce should not be 1 for a when appending to an existing key" % (seq,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, '9999-12-31', curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
    
        transaction.commit()

    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)
   
    return pub

# Use Case #4
#
# Add an additional record for a given PUB_KEY at the begining (i.e., before all other records for the key)
#   with VALID_TO date not set
#  
def prepend_pub_key(pub_key, pub_name, valid_from_date):
    # start transaction
    transaction.set_autocommit(False)
    
    # validate the VALID_FROM
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    pub_name = pub_name.strip()
    if pub_name == None or len(pub_name) == 0:
        print "error: pub_name is required" % (pub_name,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    curr_dt_tm = datetime.now(pytz.utc)
    
    try:
        if check_pub_name(pub_name, pub_key):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # get the `last` record for the existing KEY
        old_pub = Publisher.objects.filter(pub_key=pub_key, trans_spsd=datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc)).order_by('valid_from').first()
        
        if old_pub == None:
            print "error: specified pub_key: %d, does not exist. Rolling back transaction." % (pub_key,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None

        dt_fmt = '%Y-%m-%d'
        if datetime.strptime(valid_from_date, dt_fmt).date() >= old_pub.valid_from:
            print "error: valid_from: %s, must be less than old valid_from: %s" % (valid_from_date, old_pub.valid_from)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        seq = get_seq('publisher', pub_key)
        
        if seq == 1:
            print "error: got %d for sequence value. sequnce should not be 1 for a when appending to an existing key" % (seq,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, old_pub.valid_from, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
    
        transaction.commit()

    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)
   
    return pub

def insert_after_pub_key(id, pub_name, valid_from_date, valid_to_date):
    # start transaction
    transaction.set_autocommit(False)
    
    # get current date time UTC
    curr_dt_tm = datetime.now(pytz.utc)
    
    # set the db input date format
    dt_fmt = '%Y-%m-%d'
    
    # validate the VALID_FROM
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate the VALID_TO
    if not is_date_valid(valid_to_date) or valid_to_date == '9999-12-31':
        print "error: invalid valid_to date: %s" % (valid_to_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate the combination of VALID_FROM & VALID_TO dates
    if datetime.strptime(valid_to_date, dt_fmt).date() <= datetime.strptime(valid_from_date, dt_fmt).date():
        print "error: valid_to: %s, must be greater than valid_from: %s" % (valid_to_date, valid_from_date)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate NAME is not NULL or the EMPTY STRING
    if pub_name == None or len(pub_name.strip()) == 0:
        print "error: pub_name is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None

    pub_name = pub_name.strip()
    
    # validate ID is not null
    if id == None:
        print "error: PUBLISHER id is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
    
    try:
        # get the record for the `selected` publisher 
        try: 
            old_pub = Publisher.objects.get(id=id)
        except Publisher.DoesNotExist:
            print "error: specified PUBLISHER id: %d, does not exist. Rolling back transaction." % (id,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        pub_key = old_pub.pub_key
        
        # validate NAME doesn't exist for a different publisher key
        if check_pub_name(pub_name, pub_key):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # validate VALID_FROM is greater than old VALID_FROM
        if datetime.strptime(valid_from_date, dt_fmt).date() <= old_pub.valid_from:
            print "error: valid_from: %s, must be greater than old valid_from: %s" % (valid_from_date, old_pub.valid_from)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # will not have a next record if the old_pub has a VALID_TO date of 9999-12-31
        ## add check for VALID_TO date equal to 9999-12-31 later when refactoring
        
        next_pub = Publisher.objects.filter(pub_key=pub_key, valid_from__gt=old_pub.valid_from, trans_spsd=datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc)).order_by('valid_from').first()
        
        # check that there was a next publisher record
        if next_pub == None:
            print "error: next record does not exist for specified PUBLISHER id: %d. Rolling back transaction." % (id,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # validate VALID_TO is less than next VALID_TO
        if datetime.strptime(valid_to_date, dt_fmt).date() >= next_pub.valid_to:
            print "error: valid_to: %s, must be less than next valid_to: %s" % (valid_to_date, next_pub.valid_to)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # set the old TRANS_SPSD to superseded
        old_pub.trans_spsd = curr_dt_tm
        old_pub.save()
        
        # insert a copy of the old publisher with the VALID_TO date updated to the new VALID_FROM date
        cpy_old_pub = create_pub(old_pub.seq, pub_key, old_pub.name, old_pub.valid_from, valid_from_date, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        # set the next TRANS_SPSD to superseded
        next_pub.trans_spsd = curr_dt_tm
        next_pub.save()
        
        # insert a copy of the next publisher with the VALID_FROM date updated to the new VALID_to date
        cpy_next_pub = create_pub(next_pub.seq, pub_key, next_pub.name, valid_to_date, next_pub.valid_to, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        # get the next sequence number for the publisher key
        seq = get_seq('publisher', pub_key)
        
        # make sure its not equal ot 1 (seq should only be 1 for new pub keys)
        if seq == 1:
            print "error: got %d for sequence value. sequnce should not be 1 for a when appending to an existing key" % (seq,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # insert a record for the new publisher
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, valid_to_date, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        transaction.commit()
        
    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)
   
    return pub

def insert_before_pub_key(id, pub_name, valid_from_date, valid_to_date):
    # start transaction
    transaction.set_autocommit(False)
    
    # get current date time UTC
    curr_dt_tm = datetime.now(pytz.utc)
    
    # set the db input date format
    dt_fmt = '%Y-%m-%d'
    
    # validate the VALID_FROM
    if not is_date_valid(valid_from_date) or valid_from_date == '9999-12-31':
        print "error: invalid valid_from date: %s" % (valid_from_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate the VALID_TO
    if not is_date_valid(valid_to_date) or valid_to_date == '9999-12-31':
        print "error: invalid valid_to date: %s" % (valid_to_date,)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate the combination of VALID_FROM & VALID_TO dates
    if datetime.strptime(valid_to_date, dt_fmt).date() <= datetime.strptime(valid_from_date, dt_fmt).date():
        print "error: valid_to: %s, must be greater than valid_from: %s" % (valid_to_date, valid_from_date)
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
        
    # validate NAME is not NULL or the EMPTY STRING
    if pub_name == None or len(pub_name.strip()) == 0:
        print "error: pub_name is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None

    pub_name = pub_name.strip()
    
    # validate ID is not null
    if id == None:
        print "error: PUBLISHER id is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
    
    try:
        # get the record for the `selected` publisher 
        try: 
            old_pub = Publisher.objects.get(id=id)
        except Publisher.DoesNotExist:
            print "error: specified PUBLISHER id: %d, does not exist. Rolling back transaction." % (id,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        pub_key = old_pub.pub_key
        
        # validate NAME doesn't exist for a different publisher key
        if check_pub_name(pub_name, pub_key):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # validate VALID_TO is less than old VALID_TO
        if datetime.strptime(valid_to_date, dt_fmt).date() >= old_pub.valid_to:
            print "error: valid_to: %s, must be less than old valid_to: %s" % (valid_to_date, old_pub.valid_to)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # will not have a prev record if the old_pub has a SEQ of 1
        ## add check for VALID_TO date equal to 9999-12-31 later when refactoring
        
        prev_pub = Publisher.objects.filter(pub_key=pub_key, valid_from__lt=old_pub.valid_from, trans_spsd=datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc)).order_by('-valid_from').first()
        
        # check that there was a prev publisher record
        if prev_pub == None:
            print "error: prev record does not exist for specified PUBLISHER id: %d. Rolling back transaction." % (id,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        # validate VALID_FROM is greater than next VALID_FROM
        if datetime.strptime(valid_from_date, dt_fmt).date() <= prev_pub.valid_from:
            print "error: valid_from: %s, must be greater than next valid_from: %s" % (valid_from_date, prev_pub.valid_from)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # set the old TRANS_SPSD to superseded
        old_pub.trans_spsd = curr_dt_tm
        old_pub.save()
        
        # insert a copy of the old publisher with the VALID_TO date updated to the new VALID_FROM date
        cpy_old_pub = create_pub(old_pub.seq, pub_key, old_pub.name, valid_to_date, old_pub.valid_to, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        # set the next TRANS_SPSD to superseded
        prev_pub.trans_spsd = curr_dt_tm
        prev_pub.save()
        
        # insert a copy of the next publisher with the VALID_FROM date updated to the new VALID_to date
        cpy_prev_pub = create_pub(prev_pub.seq, pub_key, prev_pub.name, prev_pub.valid_from, valid_from_date, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        # get the next sequence number for the publisher key
        seq = get_seq('publisher', pub_key)
        
        # make sure its not equal ot 1 (seq should only be 1 for new pub keys)
        if seq == 1:
            print "error: got %d for sequence value. sequnce should not be 1 for a when appending to an existing key" % (seq,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        # insert a record for the new publisher
        pub = create_pub(seq, pub_key, pub_name, valid_from_date, valid_to_date, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        transaction.commit()
        
    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)
   
    return pub

def edit_pub_key(id, pub_name):
    # start transaction
    transaction.set_autocommit(False)
    
    curr_dt_tm = datetime.now(pytz.utc)
    
    # validate NAME is not NULL or the EMPTY STRING
    if pub_name == None or len(pub_name.strip()) == 0:
        print "error: pub_name is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None

    pub_name = pub_name.strip()
        
    # validate ID is not null
    if id == None:
        print "error: PUBLISHER id is required"
        transaction.rollback()
        transaction.set_autocommit(True)
        return None
    
    try:
        try: 
            # get the record for the existing KEY & SEQ
            existing_pub = Publisher.objects.get(id=id)
        except Publisher.DoesNotExist:
            print "error: specified PUBLISHER id: %d, does not exist. Rolling back transaction." % (id,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        if existing_pub.trans_spsd != datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc):
            print "error: not the most recent revision for PUBLISHER id: %d, found transaction superseded date of %s." % (id, existing_pub.trans_spsd)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
            
        pub_key = existing_pub.pub_key
        
        if check_pub_name(pub_name):
            print "error: pub_name: %s, already exists with a different pub_key" % (pub_name,)
            transaction.rollback()
            transaction.set_autocommit(True)
            return None
        
        existing_pub.trans_spsd = curr_dt_tm
        existing_pub.save()
        
        pub = create_pub(existing_pub.seq, existing_pub.pub_key, pub_name, existing_pub.valid_from, existing_pub.valid_to, curr_dt_tm, datetime(9999, 12, 31, 0, 0, tzinfo=pytz.utc))
        
        transaction.commit()

    except:
        print "error occured, rolling back transaction"
        transaction.rollback()
        transaction.set_autocommit(True)
        raise
        
    transaction.set_autocommit(True)
   
    return pub

