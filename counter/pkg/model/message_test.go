package model

import (
	"testing"
	"time"

	"github.com/hanaboso/pipes/counter/pkg/enum"
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

const (
	testPublishedMs = int64(1_700_000_000_000)
	testProcessedMs = int64(1_700_000_005_000)
)

func TestGetProcessedTimestamp(t *testing.T) {
	withBoth := ParsedMessage{Headers: map[string]interface{}{
		string(enum.Header_PublishedTimestamp): testPublishedMs,
		string(enum.Header_ProcessedTimestamp): testProcessedMs,
	}}
	if got := withBoth.GetProcessedTimestamp(); !got.Equal(time.Unix(0, testProcessedMs*1_000_000)) {
		t.Errorf("expected the processed timestamp, got %v", got)
	}

	onlyPublished := ParsedMessage{Headers: map[string]interface{}{
		string(enum.Header_PublishedTimestamp): testPublishedMs,
	}}
	if got := onlyPublished.GetProcessedTimestamp(); !got.Equal(onlyPublished.GetPublishedTimestamp()) {
		t.Errorf("expected fallback to the published timestamp, got %v", got)
	}

	invalid := ParsedMessage{Headers: map[string]interface{}{
		string(enum.Header_PublishedTimestamp): testPublishedMs,
		string(enum.Header_ProcessedTimestamp): "not-a-number",
	}}
	if got := invalid.GetProcessedTimestamp(); !got.Equal(invalid.GetPublishedTimestamp()) {
		t.Errorf("expected fallback to the published timestamp on an invalid header, got %v", got)
	}
}

func finishedTimestamp(t *testing.T, doc mongo.WriteModel) time.Time {
	t.Helper()

	update, ok := doc.(*mongo.UpdateOneModel)
	if !ok {
		t.Fatalf("expected *mongo.UpdateOneModel, got %T", doc)
	}

	pipeline, ok := update.Update.(bson.A)
	if !ok || len(pipeline) == 0 {
		t.Fatalf("expected a pipeline update, got %v", update.Update)
	}

	set, ok := pipeline[0].(bson.M)["$set"].(bson.M)
	if !ok {
		t.Fatalf("expected $set stage, got %v", pipeline[0])
	}

	outer, ok := set["finished"].(bson.M)["$cond"].(bson.A)
	if !ok || len(outer) != 3 {
		t.Fatalf("expected finished $cond, got %v", set["finished"])
	}

	inner, ok := outer[2].(bson.M)["$cond"].(bson.A)
	if !ok || len(inner) != 3 {
		t.Fatalf("expected nested finished $cond, got %v", outer[2])
	}

	finished, ok := inner[1].(time.Time)
	if !ok {
		t.Fatalf("expected time.Time finished value, got %T", inner[1])
	}

	return finished
}

func TestFinishQueriesFinishedTimestamp(t *testing.T) {
	message := ProcessMessage{Headers: map[string]interface{}{
		string(enum.Header_CorrelationId): "corr-1",
		string(enum.Header_ProcessId):     "proc-1",
	}}

	withBoth := ParsedMessage{
		Headers: map[string]interface{}{
			string(enum.Header_PublishedTimestamp): testPublishedMs,
			string(enum.Header_ProcessedTimestamp): testProcessedMs,
		},
		ProcessMessage: message,
	}
	onlyPublished := ParsedMessage{
		Headers: map[string]interface{}{
			string(enum.Header_PublishedTimestamp): testPublishedMs,
		},
		ProcessMessage: message,
	}

	expectedProcessed := time.Unix(0, testProcessedMs*1_000_000)
	expectedPublished := time.Unix(0, testPublishedMs*1_000_000)

	processDoc := withBoth.FinishProcessQuery()
	if filter, _ := processDoc.(*mongo.UpdateOneModel).Filter.(bson.M); filter["_id"] != "corr-1" {
		t.Errorf("unexpected process filter: %v", filter)
	}
	if got := finishedTimestamp(t, processDoc); !got.Equal(expectedProcessed) {
		t.Errorf("process finished: expected processed timestamp %v, got %v", expectedProcessed, got)
	}
	if got := finishedTimestamp(t, onlyPublished.FinishProcessQuery()); !got.Equal(expectedPublished) {
		t.Errorf("process finished fallback: expected published timestamp %v, got %v", expectedPublished, got)
	}

	subProcessDoc := withBoth.FinishSubProcessQuery()
	if filter, _ := subProcessDoc.(*mongo.UpdateOneModel).Filter.(bson.M); filter["_id"] != "proc-1" {
		t.Errorf("unexpected sub-process filter: %v", filter)
	}
	if got := finishedTimestamp(t, subProcessDoc); !got.Equal(expectedProcessed) {
		t.Errorf("sub-process finished: expected processed timestamp %v, got %v", expectedProcessed, got)
	}
	if got := finishedTimestamp(t, onlyPublished.FinishSubProcessQuery()); !got.Equal(expectedPublished) {
		t.Errorf("sub-process finished fallback: expected published timestamp %v, got %v", expectedPublished, got)
	}
}
