#!/usr/bin/env python3
"""Build the zustand persist payload for a demo login. Usage: portal-login.py <email> [password]"""
import json, sys, urllib.request

email = sys.argv[1] if len(sys.argv) > 1 else "super@nyansapo.gh"
password = sys.argv[2] if len(sys.argv) > 2 else "nyansapo"

req = urllib.request.Request(
    "http://127.0.0.1:3000/api/auth",
    data=json.dumps({"email": email, "password": password}).encode(),
    headers={"content-type": "application/json"},
    method="POST",
)
with urllib.request.urlopen(req) as r:
    body = json.load(r)

user = body["user"]
store = {
    "state": {
        "session": user,
        "currentSchoolId": user.get("schoolId"),
        "module": user.get("landingModule") or "dashboard",
        "queue": [],
        "lastSyncAt": None,
    },
    "version": 0,
}
print(json.dumps(store))
