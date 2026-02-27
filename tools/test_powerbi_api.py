#!/usr/bin/env python3
import os
import json
import subprocess
import urllib.request
import urllib.error

# Config
DB_NAME = os.environ.get("GLPI_DB", "glpidb")
MYSQL_BIN = os.environ.get("MYSQL_BIN", "mysql")

API_BASE = "https://api.powerbi.com/v1.0/myorg"
TIMEOUT = 20


def run_mysql(query):
    """Run a MySQL query via CLI and return stdout string."""
    try:
        # Using system mysql with implicit root privileges (as in your environment)
        proc = subprocess.run([MYSQL_BIN, DB_NAME, "-e", query], capture_output=True, text=True, timeout=10)
        if proc.returncode != 0:
            raise RuntimeError(f"mysql error: {proc.stderr.strip()}")
        return proc.stdout.strip()
    except Exception as e:
        raise RuntimeError(f"Failed to run mysql: {e}")


def get_access_token():
    """Fetch last_token from glpi_plugin_powerbireports_configs."""
    out = run_mysql("SELECT last_token, token_expiry FROM glpi_plugin_powerbireports_configs ORDER BY id DESC LIMIT 1;")
    lines = [l for l in out.splitlines() if l.strip()]
    # Expect header then one row
    if len(lines) < 2:
        raise RuntimeError("No token found in database (glpi_plugin_powerbireports_configs)")
    header = lines[0].split("\t")
    row = lines[1].split("\t")
    cols = dict(zip(header, row))
    token = cols.get("last_token")
    expiry = cols.get("token_expiry")
    if not token:
        raise RuntimeError("last_token is empty")
    return token, expiry


def get_reports():
    """Fetch all reports (id, name, group_id, report_id)."""
    out = run_mysql("SELECT id, name, group_id, report_id FROM glpi_plugin_powerbireports_reports;")
    lines = [l for l in out.splitlines() if l.strip()]
    if len(lines) < 2:
        return []
    header = lines[0].split("\t")
    reports = []
    for line in lines[1:]:
        row = line.split("\t")
        cols = dict(zip(header, row))
        reports.append(cols)
    return reports


def http_get(url, token):
    req = urllib.request.Request(url)
    req.add_header("Authorization", f"Bearer {token}")
    req.add_header("Content-Type", "application/json")
    try:
        with urllib.request.urlopen(req, timeout=TIMEOUT) as resp:
            body = resp.read().decode("utf-8")
            return resp.getcode(), body
    except urllib.error.HTTPError as e:
        try:
            body = e.read().decode("utf-8")
        except Exception:
            body = str(e)
        return e.code, body
    except Exception as e:
        return None, str(e)


def main():
    print("[PowerBI Test] Reading DB token and reports...")
    token, expiry = get_access_token()
    print(f"[PowerBI Test] Token expiry: {expiry}")
    reports = get_reports()
    if not reports:
        print("[PowerBI Test] No reports found in DB.")
        return

    for r in reports:
        name = r.get("name")
        group_id = r.get("group_id")
        report_id = r.get("report_id")
        print(f"\n[Report] {name} | group={group_id} report={report_id}")
        # 1) report metadata -> datasetId
        meta_url = f"{API_BASE}/groups/{group_id}/reports/{report_id}"
        code, body = http_get(meta_url, token)
        print(f"[Meta] HTTP {code}")
        try:
            data = json.loads(body) if body else {}
            print(f"[Meta] Full response: {json.dumps(data, indent=2)}")
        except Exception:
            data = {}
        dataset_id = data.get("datasetId")
        print(f"[Meta] datasetId: {dataset_id}")
        
        # Check modifiedDate fields
        modified_date = data.get("modifiedDate") or data.get("modifiedDateTime")
        created_date = data.get("createdDate") or data.get("createdDateTime")
        print(f"[Meta] modifiedDate: {modified_date}")
        print(f"[Meta] createdDate: {created_date}")
        
        if code != 200:
            print(f"[Meta] Response snippet: {body[:300] if body else ''}")
            continue
        
        # Show all available date fields
        date_fields = {k: v for k, v in data.items() if 'date' in k.lower() or 'time' in k.lower()}
        if date_fields:
            print(f"[Meta] All date/time fields: {date_fields}")

        if not dataset_id:
            continue

        # 2) dataset metadata
        dataset_url = f"{API_BASE}/groups/{group_id}/datasets/{dataset_id}"
        code_ds, body_ds = http_get(dataset_url, token)
        print(f"[Dataset] HTTP {code_ds}")
        try:
            dataset_data = json.loads(body_ds) if body_ds else {}
        except Exception:
            dataset_data = {}
        if code_ds == 200:
            print(f"[Dataset] Response: {json.dumps(dataset_data, indent=2)}")
            ds_dates = {k: v for k, v in dataset_data.items() if 'date' in k.lower() or 'time' in k.lower() or 'refresh' in k.lower()}
            if ds_dates:
                print(f"[Dataset] Date/time fields: {ds_dates}")
        else:
            print(f"[Dataset] Response snippet: {body_ds[:300] if body_ds else ''}")

        # 3) dataset refresh history
        refreshes_url = f"{API_BASE}/groups/{group_id}/datasets/{dataset_id}/refreshes"
        code_rf, body_rf = http_get(refreshes_url, token)
        print(f"[Refreshes] HTTP {code_rf}")
        try:
            refresh_data = json.loads(body_rf) if body_rf else {}
        except Exception:
            refresh_data = {}
        if code_rf == 200:
            values = refresh_data.get('value', [])
            if values:
                last = values[0]
                print(f"[Refreshes] Last endTime: {last.get('endTime')}")
                print(f"[Refreshes] Entry: {json.dumps(last, indent=2)}")
            else:
                print("[Refreshes] No refresh records")
        else:
            print(f"[Refreshes] Response snippet: {body_rf[:300] if body_rf else ''}")


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(f"[PowerBI Test] Error: {e}")
