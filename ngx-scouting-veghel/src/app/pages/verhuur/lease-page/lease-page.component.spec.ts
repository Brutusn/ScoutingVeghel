import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeasePageComponent } from './lease-page.component';

describe('LeasePageComponent', () => {
  let component: LeasePageComponent;
  let fixture: ComponentFixture<LeasePageComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeasePageComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LeasePageComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
