package service

import (
	"slices"
	"sort"
	"testing"

	"github.com/hanaboso/go-log/pkg/null"
	"go.mongodb.org/mongo-driver/v2/bson"

	"notifier/pkg/model"
)

// fakeRecipientRepo is an in-memory recipientRepository used by ResolveForEvent
// tests. It mirrors the production behaviour of MongoStorage's three read
// methods well enough to exercise the default-subscription branching:
//   - `FindForEvent` returns only enabled rows, just like the indexed Mongo
//     filter.
//   - `FindAllForEvent` returns every row regardless of `enabled`.
//   - `FilterSubscriptions` mirrors the topology-name filter logic.
type fakeRecipientRepo struct {
	subs   []model.Subscription
	users  []model.User
	usrErr error
}

func (f *fakeRecipientRepo) FindForEvent(tenantID, eventType string) ([]model.Subscription, error) {
	var out []model.Subscription
	for _, s := range f.subs {
		if s.TenantID == tenantID && s.SubjectType == "event_type" && s.SubjectID == eventType && s.Enabled {
			out = append(out, s)
		}
	}
	return out, nil
}

func (f *fakeRecipientRepo) FindAllForEvent(tenantID, eventType string) ([]model.Subscription, error) {
	var out []model.Subscription
	for _, s := range f.subs {
		if s.TenantID == tenantID && s.SubjectType == "event_type" && s.SubjectID == eventType {
			out = append(out, s)
		}
	}
	return out, nil
}

func (f *fakeRecipientRepo) FindAllUserIDs() ([]bson.ObjectID, error) {
	if f.usrErr != nil {
		return nil, f.usrErr
	}
	ids := make([]bson.ObjectID, len(f.users))
	for i, u := range f.users {
		ids[i] = u.ID
	}
	return ids, nil
}

func (f *fakeRecipientRepo) FindUserEmails(userIDs []bson.ObjectID) ([]string, error) {
	emails := make([]string, 0, len(userIDs))
	for _, id := range userIDs {
		for _, u := range f.users {
			if u.ID == id {
				emails = append(emails, u.Email)
				break
			}
		}
	}
	return emails, nil
}

func (f *fakeRecipientRepo) FilterSubscriptions(subs []model.Subscription, e model.EventEnvelope) []model.Subscription {
	var out []model.Subscription
	for _, sub := range subs {
		if sub.Filters != nil && len(sub.Filters.TopologyNames) > 0 {
			if e.Topology == nil || !slices.Contains(sub.Filters.TopologyNames, e.Topology.Name) {
				continue
			}
		}
		out = append(out, sub)
	}
	return out
}

// recipientServiceFor wires a recipientService over the supplied fake plus a
// minimal preset list flagging `default_event` as DefaultSubscribed and
// `explicit_event` as opt-in only.
func recipientServiceFor(repo *fakeRecipientRepo) recipientService {
	return newRecipientService(repo, []model.Preset{
		{ID: "default_event", DefaultSubscribed: true},
		{ID: "explicit_event"},
	}, null.Logger{})
}

func emailsByChannel(channels []model.ChannelRecipients, channel string) []string {
	for _, c := range channels {
		if c.Channel == channel {
			emails := append([]string(nil), c.Recipients...)
			sort.Strings(emails)
			return emails
		}
	}
	return nil
}

func mustObjectID(t *testing.T, hex string) bson.ObjectID {
	t.Helper()
	id, err := bson.ObjectIDFromHex(hex)
	if err != nil {
		t.Fatalf("invalid object id %q: %v", hex, err)
	}
	return id
}

func TestResolveForEventDefaultPresetWithoutExplicitFansOutToAllUsers(t *testing.T) {
	alice := mustObjectID(t, "000000000000000000000001")
	bob := mustObjectID(t, "000000000000000000000002")

	repo := &fakeRecipientRepo{
		users: []model.User{
			{ID: alice, Email: "alice@example.com"},
			{ID: bob, Email: "bob@example.com"},
		},
	}
	svc := recipientServiceFor(repo)

	channels, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "default_event",
	})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	got := emailsByChannel(channels, emailChannel)
	want := []string{"alice@example.com", "bob@example.com"}
	if !slices.Equal(got, want) {
		t.Fatalf("expected implicit fan-out %v, got %v", want, got)
	}
}

func TestResolveForEventDefaultPresetRespectsExplicitOptOut(t *testing.T) {
	alice := mustObjectID(t, "000000000000000000000001")
	bob := mustObjectID(t, "000000000000000000000002")

	repo := &fakeRecipientRepo{
		subs: []model.Subscription{
			{
				TenantID:    "tenant-1",
				UserID:      alice,
				SubjectType: "event_type",
				SubjectID:   "default_event",
				Channel:     emailChannel,
				Enabled:     false,
			},
		},
		users: []model.User{
			{ID: alice, Email: "alice@example.com"},
			{ID: bob, Email: "bob@example.com"},
		},
	}
	svc := recipientServiceFor(repo)

	channels, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "default_event",
	})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	got := emailsByChannel(channels, emailChannel)
	want := []string{"bob@example.com"}
	if !slices.Equal(got, want) {
		t.Fatalf("expected only bob (alice opted out), got %v", got)
	}
}

func TestResolveForEventDefaultPresetTopologyFilterShieldsUserFromImplicitPool(t *testing.T) {
	// Alice has an explicit row scoped to topology "X". For an event that
	// matches "X" she's an enabled recipient. For an event on topology "Y"
	// her explicit row is filtered out, but she must NOT fall back into the
	// implicit pool — her narrower intent takes precedence over the default.
	alice := mustObjectID(t, "000000000000000000000001")
	bob := mustObjectID(t, "000000000000000000000002")

	repo := &fakeRecipientRepo{
		subs: []model.Subscription{
			{
				TenantID:    "tenant-1",
				UserID:      alice,
				SubjectType: "event_type",
				SubjectID:   "default_event",
				Channel:     emailChannel,
				Enabled:     true,
				Filters:     &model.SubFilters{TopologyNames: []string{"X"}},
			},
		},
		users: []model.User{
			{ID: alice, Email: "alice@example.com"},
			{ID: bob, Email: "bob@example.com"},
		},
	}
	svc := recipientServiceFor(repo)

	channelsForX, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "default_event",
		Topology:  &model.TopologyRef{Name: "X"},
	})
	if err != nil {
		t.Fatalf("unexpected error for topology X: %v", err)
	}
	gotX := emailsByChannel(channelsForX, emailChannel)
	wantX := []string{"alice@example.com", "bob@example.com"}
	if !slices.Equal(gotX, wantX) {
		t.Fatalf("topology X: expected %v, got %v", wantX, gotX)
	}

	channelsForY, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "default_event",
		Topology:  &model.TopologyRef{Name: "Y"},
	})
	if err != nil {
		t.Fatalf("unexpected error for topology Y: %v", err)
	}
	gotY := emailsByChannel(channelsForY, emailChannel)
	wantY := []string{"bob@example.com"}
	if !slices.Equal(gotY, wantY) {
		t.Fatalf("topology Y: expected %v (alice filtered out, not swept back in), got %v", wantY, gotY)
	}
}

func TestResolveForEventExplicitOnlyPresetIgnoresUserPool(t *testing.T) {
	alice := mustObjectID(t, "000000000000000000000001")
	bob := mustObjectID(t, "000000000000000000000002")

	repo := &fakeRecipientRepo{
		subs: []model.Subscription{
			{
				TenantID:    "tenant-1",
				UserID:      alice,
				SubjectType: "event_type",
				SubjectID:   "explicit_event",
				Channel:     emailChannel,
				Enabled:     true,
			},
		},
		users: []model.User{
			{ID: alice, Email: "alice@example.com"},
			{ID: bob, Email: "bob@example.com"},
		},
	}
	svc := recipientServiceFor(repo)

	channels, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "explicit_event",
	})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	got := emailsByChannel(channels, emailChannel)
	want := []string{"alice@example.com"}
	if !slices.Equal(got, want) {
		t.Fatalf("expected only explicit subscriber alice, got %v", got)
	}
}

func TestResolveForEventDefaultPresetDoesNotImplicitlySubscribeSlack(t *testing.T) {
	// Slack stays opt-in even for default-subscribed presets — we never want
	// to push to a workspace that hasn't opted in. Verify a default preset
	// with one explicit Slack subscriber yields exactly that user on the
	// `slack` channel and the full user list on the `email` channel.
	alice := mustObjectID(t, "000000000000000000000001")
	bob := mustObjectID(t, "000000000000000000000002")

	repo := &fakeRecipientRepo{
		subs: []model.Subscription{
			{
				TenantID:    "tenant-1",
				UserID:      alice,
				SubjectType: "event_type",
				SubjectID:   "default_event",
				Channel:     "slack",
				Enabled:     true,
			},
		},
		users: []model.User{
			{ID: alice, Email: "alice@example.com"},
			{ID: bob, Email: "bob@example.com"},
		},
	}
	svc := recipientServiceFor(repo)

	channels, err := svc.ResolveForEvent(model.EventEnvelope{
		TenantID:  "tenant-1",
		EventType: "default_event",
	})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	gotEmail := emailsByChannel(channels, emailChannel)
	wantEmail := []string{"alice@example.com", "bob@example.com"}
	if !slices.Equal(gotEmail, wantEmail) {
		t.Fatalf("email channel: expected %v, got %v", wantEmail, gotEmail)
	}

	gotSlack := emailsByChannel(channels, "slack")
	wantSlack := []string{"alice@example.com"}
	if !slices.Equal(gotSlack, wantSlack) {
		t.Fatalf("slack channel: expected only explicit subscriber alice, got %v", gotSlack)
	}
}
