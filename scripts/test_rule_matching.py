#!/usr/bin/env python3
"""Simulates Discount_model::get_profile_rule_value matching semantics
(mirrors the PHP logic) and asserts expected outcomes for key scenarios."""

def get_profile_rule_value(rules, class_id, fee_type):
    best = None
    best_score = -1
    for rule in rules:
        rule_fee = str(rule.get('bill_category_id')) if rule.get('bill_category_id') not in (None, '') else None
        rule_class = str(rule.get('class_id')) if rule.get('class_id') not in (None, '') else None

        class_matches = (rule_class is None) or (str(class_id) == rule_class)
        fee_matches = (rule_fee is None) or (fee_type is not None and rule_fee.lower() == fee_type.lower())

        if not class_matches or not fee_matches:
            continue

        score = 0
        if rule_class is not None:
            score += 2
        if rule_fee is not None and fee_type is not None:
            score += 1

        if score > best_score:
            best_score = score
            best = rule

    return float(best['discount_value']) if best is not None else None

# rule shapes: (class_id, bill_category_id, discount_value)
def R(c, f, v):
    return {'class_id': c, 'bill_category_id': f, 'discount_value': v}

tests = [
    # (name, rules, class_id, fee_type, expected)
    ("no rules -> None", [], 5, 'feeding', None),
    ("class-only rule matches same class", [R(5, None, 30)], 5, 'feeding', 30),
    ("class-only rule NOT another class", [R(6, None, 30)], 5, 'feeding', None),
    ("fee-only rule matches fee", [R(None, 'feeding', 20)], 5, 'feeding', 20),
    ("fee-only rule skips other fee", [R(None, 'water', 20)], 5, 'feeding', None),
    ("fee-specific rule skipped when fee_type is None", [R(5, 'water', 90)], 5, None, None),
    ("class+fee rule beats class-only", [R(5, None, 30), R(5, 'feeding', 50)], 5, 'feeding', 50),
    ("class-only beats global", [R(None, None, 10), R(5, None, 30)], 5, 'feeding', 30),
    ("global rule applies when only match", [R(None, None, 15)], 5, 'feeding', 15),
    ("global rule applies with fee_type None", [R(None, None, 15)], 5, None, 15),
    ("specific class beats fee-only", [R(None, 'feeding', 20), R(5, 'feeding', 40)], 5, 'feeding', 40),
    ("other class rule ignored", [R(None, None, 10), R(6, 'feeding', 99)], 5, 'feeding', 10),
    ("100% full override via rule", [R(5, 'feeding', 100)], 5, 'feeding', 100),
]

passed = failed = 0
for name, rules, cid, ft, expected in tests:
    got = get_profile_rule_value(rules, cid, ft)
    ok = got == expected
    passed += ok
    failed += (not ok)
    print(f"{'PASS' if ok else 'FAIL'}  {name}: expected={expected} got={got}")

print(f"\n{passed} passed, {failed} failed")
exit(1 if failed else 0)
