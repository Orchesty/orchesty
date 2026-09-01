package model

import (
	"testing"
	"time"

	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
)

func TestAuditDataLinks(t *testing.T) {
	created := time.Date(2026, 7, 14, 12, 0, 0, 0, time.UTC)
	docs := AuditDataLinks(created, "corr-1", []string{"aud-1", "aud-2"})

	if len(docs) != 2 {
		t.Fatalf("expected 2 docs, got %d", len(docs))
	}

	first, ok := docs[0].(bson.M)
	if !ok {
		t.Fatalf("expected bson.M doc, got %T", docs[0])
	}

	if first["_id"] != "corr-1:aud-1" {
		t.Errorf("unexpected _id: %v", first["_id"])
	}

	if first["correlationId"] != "corr-1" {
		t.Errorf("unexpected correlationId: %v", first["correlationId"])
	}

	if first["auditDataId"] != "aud-1" {
		t.Errorf("unexpected auditDataId: %v", first["auditDataId"])
	}

	if first["created"] != created {
		t.Errorf("unexpected created: %v", first["created"])
	}

	second, _ := docs[1].(bson.M)
	if second["_id"] != "corr-1:aud-2" {
		t.Errorf("unexpected _id: %v", second["_id"])
	}
}

func TestAuditDataLinksEmpty(t *testing.T) {
	docs := AuditDataLinks(time.Now(), "corr-1", nil)

	if len(docs) != 0 {
		t.Fatalf("expected no docs, got %d", len(docs))
	}
}

func TestMergedProcessQuery(t *testing.T) {
	doc, ok := MergedProcessQuery("corr-1", 1, 2, 3, 4).(*mongo.UpdateOneModel)
	if !ok {
		t.Fatalf("expected *mongo.UpdateOneModel")
	}

	filter, ok := doc.Filter.(bson.M)
	if !ok || filter["_id"] != "corr-1" {
		t.Errorf("unexpected filter: %v", doc.Filter)
	}

	update, ok := doc.Update.(bson.M)
	if !ok {
		t.Fatalf("expected bson.M update, got %T", doc.Update)
	}

	if _, has := update["$addToSet"]; has {
		t.Errorf("update must not contain $addToSet: %v", update)
	}

	inc, ok := update["$inc"].(bson.M)
	if !ok {
		t.Fatalf("expected $inc in update: %v", update)
	}

	expected := bson.M{"ok": 1, "nok": 2, "processedCount": 3, "total": 4}
	for key, value := range expected {
		if inc[key] != value {
			t.Errorf("unexpected $inc.%s: %v", key, inc[key])
		}
	}
}
