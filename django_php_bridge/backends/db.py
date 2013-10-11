import phpserialize
from django.contrib.sessions.backends.db import SessionStore as DbStore


class SessionStore(DbStore):
  '''
    Thanks to Alex Ezell for assistance on this php-django session engine.
    http://groups.google.com/group/django-users/browse_thread/thread/f5b464379f2e4154/e358161c95e507c0

    Override the default database session backend to use php-style serialization.
    '''
  def __init__(self, session_key=None):
    # call the super class's init
    super(SessionStore, self).__init__(session_key)

  def _wrap_type(self, obj):
    out = obj
    if isinstance(obj, (list, tuple, dict)):
      out = {}
      if isinstance(obj, dict):
        iterable = obj.items()
        for key, value in iterable:
          out[key] = self._wrap_type(value)
      else:
        out["_bridge_type"] = type(obj).__name__
        out["_bridge_value"] = []
        for value in obj:
          out["_bridge_value"].append(self._wrap_type(value))
    return out

  def _unwrap_type(self, obj):
    out = obj
    if isinstance(obj, dict):
      if '_bridge_type' in obj:
        #it must be list or tuple
        iterable = obj['_bridge_value'].items()
        out = []
        for key, value in iterable:
          #as it is list or tuple, ignore the key
          out.append(self._unwrap_type(value))
        if (obj['_bridge_type'] == 'tuple'):
          out = tuple(out)
      else:
        iterable = obj.items()
        out = {}
        for key, value in iterable:
          out[self._unwrap_type(key)] = self._unwrap_type(value)
    return out

  def decode(self, session_data):
    wrapped_session_dict = phpserialize.loads(session_data)
    return self._unwrap_type(wrapped_session_dict)

  def encode(self, session_dict):
    wrapped_session_dict = self._wrap_type(session_dict)
    return phpserialize.dumps(wrapped_session_dict)
