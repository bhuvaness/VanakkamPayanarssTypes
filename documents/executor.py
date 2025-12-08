#!/usr/bin/env python3
"""
PayanarssType Tree Executor
=============================
Execute your tree structures immediately!

Usage:
    python executor.py
    
Then copy the PayanarssExecutor class to your project.
"""

import json
from typing import Dict, Any, List


class PayanarssExecutor:
    """
    Executes PayanarssType tree structures
    
    This is the main class you need!
    """
    
    def execute(self, node: Dict[str, Any], data: Dict[str, Any]) -> Dict:
        """
        Execute a PayanarssType tree
        
        Args:
            node: The tree structure (Dict)
            data: The business data context (Dict)
        
        Returns:
            Execution result (Dict)
        
        Example:
            executor = PayanarssExecutor()
            tree = {...}
            data = {"amount": 15000}
            result = executor.execute(tree, data)
        """
        return self._execute_node(node, data)
    
    def _execute_node(self, node: Dict, data: Dict) -> Dict:
        """Core recursive execution function"""
        
        node_id = node.get('Id', '')
        
        # CONTROL FLOW NODES
        if node_id == '100000000000000000010000000000001':  # IF
            return self._handle_if(node, data)
        
        if node_id == '100000000000000000010000000000002':  # ELSE
            return self._handle_else(node, data)
        
        if node_id == '100000000000000000010000000000003':  # ELSE IF
            return self._handle_else_if(node, data)
        
        if node_id == '100000000000000000010000000000011':  # FOREACH
            return self._handle_foreach(node, data)
        
        if node_id == '100000000000000000010000000000013':  # WHILE
            return self._handle_while(node, data)
        
        # COMPARISON OPERATORS
        if node_id == '100000000000000000000000000000026':  # Greater than >
            return self._handle_greater_than(node, data)
        
        if node_id == '100000000000000000000000000000027':  # Less than <
            return self._handle_less_than(node, data)
        
        if node_id == '100000000000000000000000000000028':  # Equals ==
            return self._handle_equals(node, data)
        
        if node_id == '100000000000000000000000000000029':  # Not equals !=
            return self._handle_not_equals(node, data)
        
        # ACTIONS
        if node_id == '100000000000000000000000000000032':  # Send to
            return self._handle_send_to(node, data)
        
        if node_id == '100000000000000000000000000000033':  # Approve
            return self._handle_approve(node, data)
        
        if node_id == '100000000000000000000000000000034':  # Notify
            return self._handle_notify(node, data)
        
        if node_id == '100000000000000000000000000000035':  # Create
            return self._handle_create(node, data)
        
        if node_id == '100000000000000000000000000000036':  # Update
            return self._handle_update(node, data)
        
        # FIELD REFERENCE
        if node_id.startswith('FIELD-'):
            return self._handle_field(node, data)
        
        # SQL OPERATIONS
        if node_id == '100000000000000000020000000000001':  # SELECT
            return self._handle_select(node, data)
        
        # DEFAULT: Process children
        if 'Attributes' in node and node['Attributes']:
            for attr in node['Attributes']:
                result = self._execute_node(attr, data)
                if result.get('status') == 'executed':
                    return result
        
        return {'status': 'done', 'message': f'Processed node: {node_id}'}
    
    # ============================================================
    # CONTROL FLOW HANDLERS
    # ============================================================
    
    def _handle_if(self, node: Dict, data: Dict) -> Dict:
        """IF statement handler"""
        
        if not node.get('Attributes'):
            return {'status': 'error', 'message': 'IF without condition'}
        
        condition_node = node['Attributes'][0]
        return self._execute_node(condition_node, data)
    
    def _handle_else(self, node: Dict, data: Dict) -> Dict:
        """ELSE handler"""
        
        if not node.get('Attributes'):
            return {'status': 'error', 'message': 'ELSE without action'}
        
        action_node = node['Attributes'][0]
        return self._execute_node(action_node, data)
    
    def _handle_else_if(self, node: Dict, data: Dict) -> Dict:
        """ELSE IF handler"""
        
        if not node.get('Attributes'):
            return {'status': 'error', 'message': 'ELSE IF without condition'}
        
        condition_node = node['Attributes'][0]
        return self._execute_node(condition_node, data)
    
    def _handle_foreach(self, node: Dict, data: Dict) -> Dict:
        """FOREACH loop handler"""
        
        if not node.get('Attributes'):
            return {'status': 'error', 'message': 'FOREACH without collection'}
        
        collection_node = node['Attributes'][0]
        collection_name = self._extract_field_name(collection_node.get('Id', ''))
        collection = data.get(collection_name, [])
        
        if not isinstance(collection, list):
            return {'status': 'error', 'message': f'{collection_name} is not a list'}
        
        results = []
        for item in collection:
            item_data = {**data, 'current_item': item}
            
            if len(node.get('Attributes', [])) > 1:
                logic_node = node['Attributes'][1]
                result = self._execute_node(logic_node, item_data)
                results.append(result)
        
        return {
            'status': 'executed',
            'action': 'foreach',
            'items_processed': len(results),
            'results': results
        }
    
    def _handle_while(self, node: Dict, data: Dict) -> Dict:
        """WHILE loop handler"""
        
        return {
            'status': 'executed',
            'action': 'while',
            'message': 'WHILE loop executed'
        }
    
    # ============================================================
    # COMPARISON OPERATORS
    # ============================================================
    
    def _handle_greater_than(self, node: Dict, data: Dict) -> Dict:
        """Greater than (>) operator"""
        
        field_value = data.get('field_value')
        comparison_value = node.get('Value')
        
        condition_result = field_value > comparison_value if field_value is not None else False
        
        if condition_result:
            if node.get('Attributes'):
                action_node = node['Attributes'][0]
                result = self._execute_node(action_node, data)
                result['condition_met'] = True
                return result
            else:
                return {
                    'status': 'executed',
                    'condition': f'{field_value} > {comparison_value}',
                    'result': True
                }
        
        return {
            'status': 'not_executed',
            'condition': f'{field_value} > {comparison_value}',
            'result': False
        }
    
    def _handle_less_than(self, node: Dict, data: Dict) -> Dict:
        """Less than (<) operator"""
        
        field_value = data.get('field_value')
        comparison_value = node.get('Value')
        
        condition_result = field_value < comparison_value if field_value is not None else False
        
        if condition_result and node.get('Attributes'):
            action_node = node['Attributes'][0]
            return self._execute_node(action_node, data)
        
        return {
            'status': 'not_executed' if not condition_result else 'executed',
            'result': condition_result
        }
    
    def _handle_equals(self, node: Dict, data: Dict) -> Dict:
        """Equals (==) operator"""
        
        field_value = data.get('field_value')
        comparison_value = node.get('Value')
        
        condition_result = field_value == comparison_value
        
        if condition_result and node.get('Attributes'):
            action_node = node['Attributes'][0]
            return self._execute_node(action_node, data)
        
        return {
            'status': 'not_executed' if not condition_result else 'executed',
            'result': condition_result
        }
    
    def _handle_not_equals(self, node: Dict, data: Dict) -> Dict:
        """Not equals (!=) operator"""
        
        field_value = data.get('field_value')
        comparison_value = node.get('Value')
        
        condition_result = field_value != comparison_value
        
        if condition_result and node.get('Attributes'):
            action_node = node['Attributes'][0]
            return self._execute_node(action_node, data)
        
        return {
            'status': 'not_executed' if not condition_result else 'executed',
            'result': condition_result
        }
    
    # ============================================================
    # ACTIONS
    # ============================================================
    
    def _handle_field(self, node: Dict, data: Dict) -> Dict:
        """FIELD reference handler"""
        
        field_name = self._extract_field_name(node.get('Id', ''))
        field_value = data.get(field_name.lower())
        
        if not node.get('Attributes'):
            return {
                'status': 'value',
                'field': field_name,
                'value': field_value
            }
        
        operator_node = node['Attributes'][0]
        new_data = {**data, 'field_value': field_value, 'field_name': field_name}
        
        return self._execute_node(operator_node, new_data)
    
    def _handle_send_to(self, node: Dict, data: Dict) -> Dict:
        """SEND TO action"""
        
        target = node.get('Value', 'Unknown')
        
        return {
            'status': 'executed',
            'action': 'send_to',
            'target': target,
            'message': f'✓ Routed to {target} for approval'
        }
    
    def _handle_approve(self, node: Dict, data: Dict) -> Dict:
        """APPROVE action"""
        
        return {
            'status': 'executed',
            'action': 'approve',
            'message': '✓ Auto-approved'
        }
    
    def _handle_notify(self, node: Dict, data: Dict) -> Dict:
        """NOTIFY action"""
        
        target = node.get('Value', 'Unknown')
        
        return {
            'status': 'executed',
            'action': 'notify',
            'target': target,
            'message': f'✓ Notified {target}'
        }
    
    def _handle_create(self, node: Dict, data: Dict) -> Dict:
        """CREATE action"""
        
        return {
            'status': 'executed',
            'action': 'create',
            'message': '✓ Record created'
        }
    
    def _handle_update(self, node: Dict, data: Dict) -> Dict:
        """UPDATE action"""
        
        return {
            'status': 'executed',
            'action': 'update',
            'message': '✓ Record updated'
        }
    
    # ============================================================
    # SQL OPERATIONS
    # ============================================================
    
    def _handle_select(self, node: Dict, data: Dict) -> Dict:
        """SELECT action"""
        
        return {
            'status': 'executed',
            'action': 'select',
            'message': '✓ Data retrieved'
        }
    
    # ============================================================
    # UTILITIES
    # ============================================================
    
    def _extract_field_name(self, field_id: str) -> str:
        """Extract field name from ID"""
        
        if field_id.startswith('FIELD-'):
            name = field_id.replace('FIELD-', '').rsplit('-', 1)[0]
            return name.lower()
        return field_id.lower()


# ============================================================
# EXAMPLES
# ============================================================

def run_examples():
    """Run all examples"""
    
    print("\n" + "="*70)
    print("PayanarssType Executor - Examples")
    print("="*70)
    
    executor = PayanarssExecutor()
    
    # EXAMPLE 1
    print("\n[EXAMPLE 1] Simple IF - Condition TRUE")
    print("-" * 70)
    
    tree1 = {
        "Id": "100000000000000000010000000000001",
        "Attributes": [{
            "Id": "FIELD-AMOUNT-001",
            "Attributes": [{
                "Id": "100000000000000000000000000000026",
                "Value": 10000,
                "Attributes": [{
                    "Id": "100000000000000000000000000000032",
                    "Value": "HR Manager",
                    "Attributes": []
                }]
            }]
        }]
    }
    
    data1 = {"amount": 15000}
    result1 = executor.execute(tree1, data1)
    
    print("Prompt: 'if amount > 10000, send to HR Manager'")
    print(f"Data: {data1}")
    print(f"Result:\n{json.dumps(result1, indent=2)}")
    
    # EXAMPLE 2
    print("\n[EXAMPLE 2] Simple IF - Condition FALSE")
    print("-" * 70)
    
    data2 = {"amount": 5000}
    result2 = executor.execute(tree1, data2)
    
    print("Prompt: 'if amount > 10000, send to HR Manager'")
    print(f"Data: {data2}")
    print(f"Result:\n{json.dumps(result2, indent=2)}")
    
    # EXAMPLE 3
    print("\n[EXAMPLE 3] Salary Comparison")
    print("-" * 70)
    
    tree3 = {
        "Id": "100000000000000000010000000000001",
        "Attributes": [{
            "Id": "FIELD-SALARY-001",
            "Attributes": [{
                "Id": "100000000000000000000000000000026",
                "Value": 100000,
                "Attributes": [{
                    "Id": "100000000000000000000000000000032",
                    "Value": "Director",
                    "Attributes": []
                }]
            }]
        }]
    }
    
    data3 = {"salary": 150000}
    result3 = executor.execute(tree3, data3)
    
    print("Prompt: 'if salary > 100000, send to Director'")
    print(f"Data: {data3}")
    print(f"Result:\n{json.dumps(result3, indent=2)}")
    
    # EXAMPLE 4
    print("\n[EXAMPLE 4] FOREACH Loop")
    print("-" * 70)
    
    tree4 = {
        "Id": "100000000000000000010000000000011",
        "Attributes": [{
            "Id": "FIELD-EMPLOYEES-001",
            "Attributes": []
        }]
    }
    
    data4 = {
        "employees": [
            {"name": "John", "salary": 50000},
            {"name": "Jane", "salary": 60000},
            {"name": "Bob", "salary": 45000}
        ]
    }
    result4 = executor.execute(tree4, data4)
    
    print("Prompt: 'for each employee, process'")
    print(f"Data: {data4}")
    print(f"Result:\n{json.dumps(result4, indent=2)}")
    
    print("\n" + "="*70)
    print("Examples Complete!")
    print("="*70 + "\n")


if __name__ == "__main__":
    run_examples()
