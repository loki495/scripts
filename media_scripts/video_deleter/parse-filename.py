#!/usr/bin/env python
import sys
import PTN
import json

info = PTN.parse(sys.argv[1])

print(json.dumps(info))
